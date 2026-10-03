// Debug script for student authentication
// Add this script to student-auth.html to debug the email verification process

(function() {
    // Set up console logging to display on the page
    const consoleDiv = document.createElement('div');
    consoleDiv.id = 'debug-console';
    consoleDiv.style.cssText = `
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 200px;
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        font-family: monospace;
        padding: 10px;
        overflow-y: auto;
        z-index: 9999;
        display: none;
    `;
    
    const toggleBtn = document.createElement('button');
    toggleBtn.textContent = 'Toggle Debug Console';
    toggleBtn.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 10000;
        padding: 5px 10px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    `;
    
    toggleBtn.addEventListener('click', function() {
        consoleDiv.style.display = consoleDiv.style.display === 'none' ? 'block' : 'none';
    });
    
    document.body.appendChild(consoleDiv);
    document.body.appendChild(toggleBtn);
    
    // Save original console methods
    const originalConsole = {
        log: console.log,
        error: console.error,
        warn: console.warn,
        info: console.info
    };
    
    // Override console methods to also display in our debug div
    function formatTime() {
        const now = new Date();
        return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}.${now.getMilliseconds().toString().padStart(3, '0')}`;
    }
    
    function logToConsole(type, args) {
        const line = document.createElement('div');
        const timeString = formatTime();
        line.innerHTML = `<span style="color: #aaa;">[${timeString}]</span> <span style="color: ${type === 'error' ? '#ff6b6b' : type === 'warn' ? '#feca57' : type === 'info' ? '#54a0ff' : '#fff'}">${Array.from(args).map(arg => typeof arg === 'object' ? JSON.stringify(arg) : arg).join(' ')}</span>`;
        consoleDiv.appendChild(line);
        consoleDiv.scrollTop = consoleDiv.scrollHeight;
    }
    
    console.log = function() {
        originalConsole.log.apply(console, arguments);
        logToConsole('log', arguments);
    };
    
    console.error = function() {
        originalConsole.error.apply(console, arguments);
        logToConsole('error', arguments);
    };
    
    console.warn = function() {
        originalConsole.warn.apply(console, arguments);
        logToConsole('warn', arguments);
    };
    
    console.info = function() {
        originalConsole.info.apply(console, arguments);
        logToConsole('info', arguments);
    };
    
    // Add AJAX request debugging
    const originalFetch = window.fetch;
    window.fetch = function() {
        const url = arguments[0];
        const options = arguments[1] || {};
        const method = options.method || 'GET';
        const body = options.body;
        
        console.info(`Fetch request: ${method} ${url}`);
        if (body) {
            console.info(`Request body: ${body}`);
        }
        
        return originalFetch.apply(this, arguments)
            .then(response => {
                // Clone the response so we can log it and still return it
                const clone = response.clone();
                clone.text().then(text => {
                    try {
                        const json = JSON.parse(text);
                        console.info(`Response from ${url}: `, json);
                    } catch (e) {
                        console.info(`Response from ${url}: ${text}`);
                    }
                });
                return response;
            })
            .catch(error => {
                console.error(`Fetch error for ${url}: ${error.message}`);
                throw error;
            });
    };
    
    // Debug verification code inputs
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            // Add debug info on verification functionality
            console.info("Debug mode enabled for email verification");
            
            // Monitor login button clicks
            const loginStep1Btn = document.getElementById('login-step-1-btn');
            if (loginStep1Btn) {
                const originalClickHandler = loginStep1Btn.onclick;
                loginStep1Btn.onclick = function(e) {
                    console.info("Login Step 1 button clicked");
                    return originalClickHandler && originalClickHandler.call(this, e);
                };
            }
            
            // Monitor signup button clicks
            const signupStep1Btn = document.getElementById('signup-step-1-btn');
            if (signupStep1Btn) {
                const originalClickHandler = signupStep1Btn.onclick;
                signupStep1Btn.onclick = function(e) {
                    console.info("Signup Step 1 button clicked");
                    return originalClickHandler && originalClickHandler.call(this, e);
                };
            }
            
            // Add test buttons for direct testing
            const testButtons = document.createElement('div');
            testButtons.style.cssText = `
                position: fixed;
                top: 50px;
                right: 10px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 5px;
            `;
            
            const createTestBtn = (text, action) => {
                const btn = document.createElement('button');
                btn.textContent = text;
                btn.style.cssText = `
                    padding: 5px 10px;
                    background: #2ecc71;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                `;
                btn.addEventListener('click', action);
                return btn;
            };
            
            // Add a button to directly test sendVerificationCode
            const testSendCodeBtn = createTestBtn('Test Send Code', function() {
                const email = prompt("Enter email to test:", "test@example.com");
                if (email) {
                    console.info(`Testing sending code to ${email}`);
                    sendVerificationCode(email, false);
                }
            });
            testButtons.appendChild(testSendCodeBtn);
            
            // Add a button to check table
            const checkTableBtn = createTestBtn('Check DB Table', function() {
                window.open('check_table.php', '_blank');
            });
            testButtons.appendChild(checkTableBtn);
            
            // Add a button to run test script
            const runTestBtn = createTestBtn('Run Test Script', function() {
                window.open('test_email_verification.php', '_blank');
            });
            testButtons.appendChild(runTestBtn);
            
            document.body.appendChild(testButtons);
        }, 1000);
    });
})(); 