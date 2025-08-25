const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const WebSocket = require('ws');

/**
 * BVOTE 2025 - Advanced Auto Login System
 * Integrated with MoreLogin + Puppeteer for social platform automation
 */

class BVoteAutoLogin {
    constructor() {
        this.config = this.loadConfig();
        this.status = { status: 'initializing', timestamp: Date.now() };
        this.activeSessions = new Map();
        this.browser = null;
        this.wsServer = null;

        console.log('🚀 BVOTE 2025 Auto Login System starting...');
        this.updateStatus('starting');
    }

    loadConfig() {
        try {
            const configPath = path.join(__dirname, 'config.json');
            if (fs.existsSync(configPath)) {
                return JSON.parse(fs.readFileSync(configPath, 'utf8'));
            }
        } catch (error) {
            console.warn('⚠️  Config file not found, using defaults');
        }

        // Default configuration
        return {
            browser: {
                headless: false,
                devtools: false,
                slowMo: 50
            },
            websocket: {
                port: 8080,
                enabled: true
            },
            security: {
                maxSessions: 10,
                sessionTimeout: 300000, // 5 minutes
                retryAttempts: 3
            },
            platforms: {
                facebook: {
                    loginUrl: 'https://www.facebook.com/login',
                    selectors: {
                        username: '#email',
                        password: '#pass',
                        loginButton: '#loginbutton',
                        twoFactorCode: '[name="approvals_code"]',
                        checkpoint: '[data-testid="checkpoint_subtitle"]'
                    }
                },
                gmail: {
                    loginUrl: 'https://accounts.google.com/signin',
                    selectors: {
                        username: '#identifierId',
                        password: '[name="password"]',
                        nextButton: '#identifierNext',
                        passwordNext: '#passwordNext',
                        twoFactorCode: '#totpPin'
                    }
                },
                instagram: {
                    loginUrl: 'https://www.instagram.com/accounts/login/',
                    selectors: {
                        username: '[name="username"]',
                        password: '[name="password"]',
                        loginButton: '[type="submit"]',
                        twoFactorCode: '[name="verificationCode"]'
                    }
                }
            }
        };
    }

    async initialize() {
        try {
            // Initialize browser
            await this.initializeBrowser();

            // Start WebSocket server
            if (this.config.websocket.enabled) {
                await this.startWebSocketServer();
            }

            // Start queue processor
            this.startQueueProcessor();

            this.updateStatus('running');
            console.log('✅ Auto Login System fully initialized');

        } catch (error) {
            console.error('❌ Initialization failed:', error);
            this.updateStatus('error', error.message);
        }
    }

    async initializeBrowser() {
        const browserOptions = {
            headless: this.config.browser.headless,
            devtools: this.config.browser.devtools,
            slowMo: this.config.browser.slowMo,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--window-size=1366,768'
            ]
        };

        console.log('🌐 Launching browser...');
        this.browser = await puppeteer.launch(browserOptions);
        console.log('✅ Browser launched successfully');
    }

    async startWebSocketServer() {
        this.wsServer = new WebSocket.Server({ port: this.config.websocket.port });

        this.wsServer.on('connection', (ws) => {
            console.log('🔌 WebSocket client connected');

            ws.on('message', async (message) => {
                try {
                    const data = JSON.parse(message);
                    await this.handleWebSocketMessage(ws, data);
                } catch (error) {
                    this.sendWebSocketError(ws, 'Invalid JSON message', error);
                }
            });

            ws.on('close', () => {
                console.log('🔌 WebSocket client disconnected');
            });
        });

        console.log(`🔌 WebSocket server running on port ${this.config.websocket.port}`);
    }

    async handleWebSocketMessage(ws, data) {
        switch (data.type) {
            case 'login_request':
                await this.processLoginRequest(ws, data.data);
                break;

            case 'status_check':
                this.sendWebSocketMessage(ws, {
                    type: 'status_response',
                    data: this.getSystemStatus()
                });
                break;

            case 'cancel_session':
                await this.cancelSession(data.session_id);
                break;

            default:
                this.sendWebSocketError(ws, 'Unknown message type');
        }
    }

    startQueueProcessor() {
        const queueDir = path.join(__dirname, '..', 'data', 'automation_queue');

        if (!fs.existsSync(queueDir)) {
            fs.mkdirSync(queueDir, { recursive: true });
        }

        // Process existing queue files
        setInterval(() => {
            this.processQueueFiles(queueDir);
        }, 5000); // Check every 5 seconds

        console.log('📁 Queue processor started');
    }

    async processQueueFiles(queueDir) {
        try {
            const files = fs.readdirSync(queueDir)
                .filter(file => file.endsWith('.json') && !file.includes('_status'));

            for (const file of files) {
                const filePath = path.join(queueDir, file);
                const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

                await this.processLoginRequest(null, data);

                // Remove processed file
                fs.unlinkSync(filePath);
            }

        } catch (error) {
            console.error('Queue processing error:', error);
        }
    }

    async processLoginRequest(ws, loginData) {
        const sessionId = loginData.session_id;

        try {
            console.log(`🔐 Processing login request: ${sessionId}`);

            // Validate session limits
            if (this.activeSessions.size >= this.config.security.maxSessions) {
                throw new Error('Maximum concurrent sessions reached');
            }

            // Create session tracking
            const session = {
                id: sessionId,
                platform: loginData.platform,
                startTime: Date.now(),
                status: 'processing',
                ws: ws,
                page: null
            };

            this.activeSessions.set(sessionId, session);
            this.sendStatusUpdate(session, 'processing', 'Khởi tạo phiên đăng nhập...');

            // Execute login flow
            const result = await this.executeLoginFlow(session, loginData);

            // Update session status
            session.status = 'completed';
            this.sendStatusUpdate(session, 'success', 'Đăng nhập thành công!', result);

        } catch (error) {
            console.error(`❌ Login failed for ${sessionId}:`, error);
            const session = this.activeSessions.get(sessionId);
            if (session) {
                session.status = 'failed';
                this.sendStatusUpdate(session, 'error', error.message);
            }
        } finally {
            // Cleanup session
            setTimeout(() => {
                this.cleanupSession(sessionId);
            }, 10000); // Keep session for 10 seconds for final status check
        }
    }

    async executeLoginFlow(session, loginData) {
        const { platform, credentials, settings } = loginData;
        const platformConfig = this.config.platforms[platform];

        if (!platformConfig) {
            throw new Error(`Unsupported platform: ${platform}`);
        }

        // Create new page with custom settings
        const page = await this.createCustomPage(settings);
        session.page = page;

        this.sendStatusUpdate(session, 'processing', `Kết nối tới ${platform}...`);

        // Navigate to login page
        await page.goto(platformConfig.loginUrl, { waitUntil: 'networkidle0' });

        this.sendStatusUpdate(session, 'processing', 'Điền thông tin đăng nhập...');

        // Execute platform-specific login
        const result = await this.executePlatformLogin(page, platform, credentials, platformConfig);

        this.sendStatusUpdate(session, 'processing', 'Phê duyệt đăng nhập...');

        // Wait for successful login indicators
        await this.waitForLoginSuccess(page, platform);

        return result;
    }

    async createCustomPage(settings) {
        const page = await this.browser.newPage();

        // Set viewport
        if (settings.viewport) {
            await page.setViewport(settings.viewport);
        }

        // Set user agent
        if (settings.user_agent) {
            await page.setUserAgent(settings.user_agent);
        }

        // Set delays for human-like behavior
        if (settings.delays) {
            page.defaultTimeout = settings.delays.page_load || 30000;
        }

        // Block unnecessary resources for faster loading
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const resourceType = req.resourceType();
            if (['image', 'stylesheet', 'font'].includes(resourceType)) {
                req.abort();
            } else {
                req.continue();
            }
        });

        return page;
    }

    async executePlatformLogin(page, platform, credentials, config) {
        switch (platform) {
            case 'facebook':
                return await this.facebookLogin(page, credentials, config);
            case 'gmail':
                return await this.gmailLogin(page, credentials, config);
            case 'instagram':
                return await this.instagramLogin(page, credentials, config);
            default:
                throw new Error(`Login flow not implemented for ${platform}`);
        }
    }

    async facebookLogin(page, credentials, config) {
        const { selectors } = config;

        // Wait for login form
        await page.waitForSelector(selectors.username);

        // Fill username
        await this.humanType(page, selectors.username, credentials.username);
        await this.randomDelay(500, 1000);

        // Fill password
        await this.humanType(page, selectors.password, credentials.password);
        await this.randomDelay(500, 1000);

        // Click login button
        await page.click(selectors.loginButton);

        // Handle potential 2FA or checkpoint
        return await this.handleFacebookPostLogin(page, credentials, selectors);
    }

    async handleFacebookPostLogin(page, credentials, selectors) {
        try {
            // Wait for either success redirect or 2FA prompt
            await page.waitForNavigation({ timeout: 10000 });

            // Check for 2FA prompt
            const twoFactorElement = await page.$(selectors.twoFactorCode);
            if (twoFactorElement && credentials.otp) {
                await this.humanType(page, selectors.twoFactorCode, credentials.otp);
                await page.keyboard.press('Enter');
                await page.waitForNavigation({ timeout: 15000 });
            }

            // Check for checkpoint
            const checkpointElement = await page.$(selectors.checkpoint);
            if (checkpointElement) {
                return {
                    status: 'checkpoint',
                    message: 'Account requires security checkpoint verification',
                    url: page.url()
                };
            }

            return {
                status: 'success',
                url: page.url(),
                cookies: await page.cookies()
            };

        } catch (error) {
            throw new Error(`Facebook post-login handling failed: ${error.message}`);
        }
    }

    async gmailLogin(page, credentials, config) {
        const { selectors } = config;

        // Wait for email input
        await page.waitForSelector(selectors.username);

        // Fill email
        await this.humanType(page, selectors.username, credentials.username);
        await page.click(selectors.nextButton);

        // Wait for password input
        await page.waitForSelector(selectors.password, { timeout: 10000 });
        await this.randomDelay(1000, 2000);

        // Fill password
        await this.humanType(page, selectors.password, credentials.password);
        await page.click(selectors.passwordNext);

        // Handle 2FA if needed
        try {
            await page.waitForSelector(selectors.twoFactorCode, { timeout: 5000 });
            if (credentials.otp) {
                await this.humanType(page, selectors.twoFactorCode, credentials.otp);
                await page.keyboard.press('Enter');
            }
        } catch (e) {
            // No 2FA required
        }

        await page.waitForNavigation({ timeout: 15000 });

        return {
            status: 'success',
            url: page.url(),
            cookies: await page.cookies()
        };
    }

    async instagramLogin(page, credentials, config) {
        const { selectors } = config;

        // Wait for login form
        await page.waitForSelector(selectors.username);

        // Fill credentials
        await this.humanType(page, selectors.username, credentials.username);
        await this.randomDelay(500, 1000);

        await this.humanType(page, selectors.password, credentials.password);
        await this.randomDelay(500, 1000);

        // Submit form
        await page.click(selectors.loginButton);

        // Handle 2FA if present
        try {
            await page.waitForSelector(selectors.twoFactorCode, { timeout: 8000 });
            if (credentials.otp) {
                await this.humanType(page, selectors.twoFactorCode, credentials.otp);
                await page.keyboard.press('Enter');
            }
        } catch (e) {
            // No 2FA required
        }

        await page.waitForNavigation({ timeout: 15000 });

        return {
            status: 'success',
            url: page.url(),
            cookies: await page.cookies()
        };
    }

    async waitForLoginSuccess(page, platform) {
        // Platform-specific success indicators
        const successIndicators = {
            facebook: () => page.url().includes('facebook.com') && !page.url().includes('login'),
            gmail: () => page.url().includes('myaccount.google.com') || page.url().includes('mail.google.com'),
            instagram: () => page.url() === 'https://www.instagram.com/' || page.url().includes('instagram.com/accounts/onetap')
        };

        const checkSuccess = successIndicators[platform];
        if (checkSuccess) {
            let attempts = 0;
            while (attempts < 30 && !await checkSuccess()) {
                await this.randomDelay(1000, 2000);
                attempts++;
            }
        }
    }

    // Utility methods for human-like behavior
    async humanType(page, selector, text) {
        await page.focus(selector);
        await page.keyboard.type(text, { delay: this.randomBetween(50, 150) });
    }

    async randomDelay(min, max) {
        const delay = this.randomBetween(min, max);
        await new Promise(resolve => setTimeout(resolve, delay));
    }

    randomBetween(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    // Session management
    sendStatusUpdate(session, status, message, data = null) {
        const update = {
            session_id: session.id,
            status: status,
            message: message,
            data: data,
            timestamp: Date.now()
        };

        // Send via WebSocket if connected
        if (session.ws && session.ws.readyState === WebSocket.OPEN) {
            this.sendWebSocketMessage(session.ws, {
                type: 'status_update',
                data: update
            });
        }

        // Save to file for API polling
        this.saveStatusToFile(session.id, update);

        console.log(`📊 Session ${session.id}: ${status} - ${message}`);
    }

    saveStatusToFile(sessionId, status) {
        const statusFile = path.join(__dirname, '..', 'data', 'automation_queue', `${sessionId}_status.json`);
        fs.writeFileSync(statusFile, JSON.stringify(status, null, 2));
    }

    async cleanupSession(sessionId) {
        const session = this.activeSessions.get(sessionId);
        if (session) {
            if (session.page) {
                await session.page.close();
            }
            this.activeSessions.delete(sessionId);
            console.log(`🧹 Session ${sessionId} cleaned up`);
        }
    }

    async cancelSession(sessionId) {
        await this.cleanupSession(sessionId);
    }

    getSystemStatus() {
        return {
            status: this.status.status,
            active_sessions: this.activeSessions.size,
            max_sessions: this.config.security.maxSessions,
            uptime: Date.now() - this.status.timestamp,
            browser_connected: !!this.browser,
            websocket_connected: !!this.wsServer
        };
    }

    updateStatus(status, message = null) {
        this.status = {
            status: status,
            message: message,
            timestamp: Date.now(),
            pid: process.pid
        };

        // Save status to file
        const statusFile = path.join(__dirname, 'status.json');
        fs.writeFileSync(statusFile, JSON.stringify(this.status, null, 2));
    }

    // WebSocket utilities
    sendWebSocketMessage(ws, message) {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(message));
        }
    }

    sendWebSocketError(ws, error, details = null) {
        this.sendWebSocketMessage(ws, {
            type: 'error',
            error: error,
            details: details
        });
    }

    // Graceful shutdown
    async shutdown() {
        console.log('🛑 Shutting down Auto Login System...');

        this.updateStatus('shutting_down');

        // Close all active sessions
        for (const [sessionId] of this.activeSessions) {
            await this.cleanupSession(sessionId);
        }

        // Close browser
        if (this.browser) {
            await this.browser.close();
        }

        // Close WebSocket server
        if (this.wsServer) {
            this.wsServer.close();
        }

        this.updateStatus('stopped');
        console.log('✅ Auto Login System stopped');
    }
}

// Initialize and start the system
const autoLogin = new BVoteAutoLogin();

autoLogin.initialize().catch(error => {
    console.error('❌ Failed to start Auto Login System:', error);
    process.exit(1);
});

// Graceful shutdown handlers
process.on('SIGINT', async () => {
    await autoLogin.shutdown();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    await autoLogin.shutdown();
    process.exit(0);
});

console.log('🎯 BVOTE 2025 Auto Login System ready for requests');
