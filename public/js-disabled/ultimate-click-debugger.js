/**
 * ULTIMATE CLICK DEBUGGER - Find what's blocking clicks
 */

console.error('🔍🔍🔍 ULTIMATE CLICK DEBUGGER ACTIVE 🔍🔍🔍');

// 1. Track ALL event listeners
const listeners = new WeakMap();
const originalAddEventListener = EventTarget.prototype.addEventListener;
const originalRemoveEventListener = EventTarget.prototype.removeEventListener;

EventTarget.prototype.addEventListener = function(type, listener, options) {
    if (type === 'click' || type === 'mousedown' || type === 'mouseup' || type === 'pointerdown') {
        console.warn(`[EVENT ADDED] ${type} on`, this, 'listener:', listener.toString().substring(0, 100));
        
        if (!listeners.has(this)) {
            listeners.set(this, []);
        }
        listeners.get(this).push({type, listener, options});
    }
    return originalAddEventListener.call(this, type, listener, options);
};

// 2. Test click on first link
setTimeout(() => {
    const firstLink = document.querySelector('a[href]');
    if (firstLink) {
        console.log('🔍 TESTING FIRST LINK:', firstLink.href);
        
        // Check computed styles
        const styles = window.getComputedStyle(firstLink);
        console.log('Computed styles:', {
            pointerEvents: styles.pointerEvents,
            cursor: styles.cursor,
            position: styles.position,
            zIndex: styles.zIndex,
            display: styles.display,
            visibility: styles.visibility
        });
        
        // Check for overlapping elements
        const rect = firstLink.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const elementAtPoint = document.elementFromPoint(centerX, centerY);
        
        console.log('Element at center of link:', elementAtPoint);
        if (elementAtPoint !== firstLink) {
            console.error('❌ BLOCKING ELEMENT FOUND:', elementAtPoint);
            
            // Try to find what's blocking
            let current = elementAtPoint;
            while (current && current !== firstLink) {
                console.log('Blocking element in chain:', current, {
                    tagName: current.tagName,
                    className: current.className,
                    id: current.id,
                    zIndex: window.getComputedStyle(current).zIndex
                });
                current = current.parentElement;
            }
        }
        
        // List all event listeners
        if (listeners.has(firstLink)) {
            console.log('Event listeners on link:', listeners.get(firstLink));
        }
    }
}, 1000);

// 3. Create test button that definitely works
const testButton = document.createElement('button');
testButton.textContent = '🔴 TEST CLICK - Should open Dashboard';
testButton.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: red;
    color: white;
    padding: 20px;
    border: 3px solid black;
    cursor: pointer;
    z-index: 2147483647;
    font-size: 18px;
    font-weight: bold;
`;

// Use most basic click handler possible
testButton.onclick = function() {
    console.log('🔴 TEST BUTTON CLICKED');
    window.location.href = '/admin';
};

document.body.appendChild(testButton);

// 4. Monitor what happens on ANY click
document.addEventListener('click', function(e) {
    console.log('🔍 CLICK DETECTED:', {
        target: e.target,
        currentTarget: e.currentTarget,
        defaultPrevented: e.defaultPrevented,
        propagationStopped: e.cancelBubble,
        eventPhase: e.eventPhase,
        isTrusted: e.isTrusted,
        timeStamp: e.timeStamp
    });
}, true);

document.addEventListener('mousedown', function(e) {
    console.log('🔍 MOUSEDOWN DETECTED:', e.target);
}, true);

// 5. Check for CSS that might block
const sheets = document.styleSheets;
let suspiciousRules = [];
for (let sheet of sheets) {
    try {
        for (let rule of sheet.cssRules || []) {
            if (rule.cssText && (
                rule.cssText.includes('pointer-events: none') ||
                rule.cssText.includes('user-select: none') ||
                rule.cssText.includes('z-index: 9999')
            )) {
                suspiciousRules.push(rule.cssText);
            }
        }
    } catch (e) {
        // Cross-origin
    }
}

if (suspiciousRules.length > 0) {
    console.warn('⚠️ SUSPICIOUS CSS RULES FOUND:', suspiciousRules);
}

console.error('🔍 DEBUGGER READY - Try clicking a link and check console');
console.error('🔴 Also try the red TEST BUTTON at bottom right');