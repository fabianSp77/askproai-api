// Override Livewire's failure modal completely
//console.log('Livewire Override Loading...');

// Wait for Livewire to be available
function overrideLivewire() {
    if (typeof window.Livewire === 'undefined') {
        setTimeout(overrideLivewire, 100);
        return;
    }
    
    //console.log('Overriding Livewire failure handling...');
    
    // Override the showHtmlModal function that causes the issue
    if (window.Livewire && window.Livewire.components) {
        // Find and override showHtmlModal globally
        const originalShowHtmlModal = window.showHtmlModal || function() {};
        window.showHtmlModal = function(html) {
            //console.log('Blocked showHtmlModal call');
            if (html && html.includes('419')) {
                //console.log('Blocked 419 modal');
                return;
            }
            return originalShowHtmlModal.apply(this, arguments);
        };
    }
    
    // Override document.write to prevent modal display
    const originalWrite = document.write;
    document.write = function(content) {
        if (content && (content.includes('419') || content.includes('Page Expired'))) {
            //console.log('Blocked document.write with 419 content');
            return;
        }
        return originalWrite.apply(this, arguments);
    };
    
    // Override Livewire's connection error handling
    if (window.Livewire && window.Livewire.connection) {
        window.Livewire.connection.showFailureModal = function() {
            //console.log('Overridden showFailureModal - doing nothing');
        };
    }
    
    // Hook into Livewire lifecycle
    if (window.Livewire) {
        // Override onError globally
        window.Livewire.onError = function(error) {
            //console.log('Livewire error intercepted:', error);
            if (error && error.status === 419) {
                //console.log('Ignoring 419 error');
                return false;
            }
        };
        
        // Override on individual components
        window.Livewire.hook('component.initialized', (component) => {
            if (component.connection) {
                component.connection.showFailureModal = function() {
                    //console.log('Component showFailureModal overridden');
                };
            }
        });
        
        // Override sendRequest to handle 419
        const components = window.Livewire.components;
        if (components && components.componentsById) {
            Object.values(components.componentsById).forEach(component => {
                if (component.connection && component.connection.sendRequest) {
                    const originalSendRequest = component.connection.sendRequest;
                    component.connection.sendRequest = function(...args) {
                        const promise = originalSendRequest.apply(this, args);
                        promise.catch(error => {
                            if (error && error.status === 419) {
                                //console.log('Caught 419 in sendRequest');
                                return Promise.resolve();
                            }
                            throw error;
                        });
                        return promise;
                    };
                }
            });
        }
    }
    
    //console.log('Livewire override complete');
}

// Start override process
overrideLivewire();

// Also override any future Livewire initializations
if (window.Livewire) {
    window.Livewire.start = new Proxy(window.Livewire.start || function() {}, {
        apply: function(target, thisArg, argumentsList) {
            //console.log('Livewire.start intercepted');
            const result = target.apply(thisArg, argumentsList);
            setTimeout(overrideLivewire, 100);
            return result;
        }
    });
}