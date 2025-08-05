// Navigation Debug Helper - Issue #479
// Add this to check if navigation elements are clickable

document.addEventListener("DOMContentLoaded", function() {
    console.log("[Navigation Debug] Checking navigation elements...");
    
    // Test sidebar navigation elements
    const navElements = document.querySelectorAll(".fi-sidebar-nav a, .fi-sidebar-item, .fi-sidebar-nav button");
    
    navElements.forEach((element, index) => {
        const styles = window.getComputedStyle(element);
        const pointerEvents = styles.pointerEvents;
        const zIndex = styles.zIndex;
        const position = styles.position;
        
        console.log(`[Navigation Debug] Element ${index + 1}:`, {
            text: element.textContent.trim().substring(0, 20),
            pointerEvents: pointerEvents,
            zIndex: zIndex,
            position: position,
            clickable: pointerEvents \!== "none"
        });
        
        // Add visual indicator for debugging
        if (pointerEvents === "none") {
            element.style.border = "2px solid red";
            element.title = "NOT CLICKABLE - pointer-events: none";
        } else {
            element.style.border = "1px solid green";
            element.title = "CLICKABLE";
        }
    });
    
    // Check for blocking overlays
    const overlays = document.querySelectorAll("[style*=\"position: fixed\"], [style*=\"position: absolute\"]");
    console.log(`[Navigation Debug] Found ${overlays.length} potential blocking overlays`);
    
    overlays.forEach((overlay, index) => {
        const styles = window.getComputedStyle(overlay);
        if (styles.pointerEvents \!== "none" && styles.zIndex > 40) {
            console.warn(`[Navigation Debug] Potential blocking overlay ${index}:`, overlay);
        }
    });
});

// Test function to call from browser console
window.testNavigation = function() {
    const links = document.querySelectorAll(".fi-sidebar-nav a");
    links.forEach((link, i) => {
        console.log(`Link ${i}: ${link.textContent} - clickable: ${window.getComputedStyle(link).pointerEvents \!== "none"}`);
    });
};

console.log("[Navigation Debug] Use window.testNavigation() to test navigation links");
