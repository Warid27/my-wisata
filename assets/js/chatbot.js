/**
 * MyWisata Chatbot - Powered by Puter.js
 * AI Assistant for ticket booking and event information
 */

class MyWisataChatbot {
    constructor() {
        this.config = window.ChatbotConfig || {};
        this.isOpen = false;
        this.isAuthenticated = false;
        this.isTyping = false;
        this.chatHistory = [];
        this.currentStreamMessage = null;
        
        this.init();
    }
    
    init() {
        // Ensure chatHistory is initialized
        if (!this.chatHistory || !Array.isArray(this.chatHistory)) {
            this.chatHistory = [];
        }
        
        this.bindEvents();
        this.loadChatHistory();
        this.checkAuthStatus();
        
        // Add welcome message for returning users
        if (this.chatHistory && this.chatHistory.length === 0) {
            this.addSystemMessage('Halo! 👋 Saya adalah asisten AI MyWisata. Ada yang bisa saya bantu?');
        }
    }
    
    bindEvents() {
        // Toggle chat
        const toggleBtn = document.getElementById('chatbot-toggle');
        const closeBtn = document.getElementById('chatbot-close');
        const sendBtn = document.getElementById('chatbot-send');
        const input = document.getElementById('chatbot-input');
        
        toggleBtn?.addEventListener('click', () => this.toggleChat());
        closeBtn?.addEventListener('click', () => this.closeChat());
        sendBtn?.addEventListener('click', () => this.sendMessage());
        
        // Enter key to send
        input?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', () => this.handleQuickAction(btn.dataset.action));
        });
        
        // Auto-resize textarea
        input?.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });
    }
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        const container = document.getElementById('chatbot-container');
        const toggleBtn = document.getElementById('chatbot-toggle');
        const input = document.getElementById('chatbot-input');
        
        if (this.isOpen) {
            container.classList.add('show');
            toggleBtn.classList.add('d-none');
            input?.focus();
        } else {
            container.classList.remove('show');
            toggleBtn.classList.remove('d-none');
        }
    }
    
    closeChat() {
        this.isOpen = false;
        document.getElementById('chatbot-container').classList.remove('show');
        document.getElementById('chatbot-toggle').classList.remove('d-none');
    }
    
    async checkAuthStatus() {
        if (typeof puter !== 'undefined') {
            try {
                this.isAuthenticated = await puter.auth.isSignedIn();
                if (!this.isAuthenticated && this.config.puterAuthRequired) {
                    // Show subtle auth prompt, not intrusive
                    this.addSystemMessage('🔐 Untuk pengalaman terbaik, silakan login dengan Puter untuk menyimpan riwayat chat Anda.');
                }
            } catch (error) {
                console.error('Error checking auth status:', error);
            }
        }
    }
    
    async authenticate() {
        if (typeof puter === 'undefined') {
            this.addSystemMessage('❌ Puter.js tidak dimuat. Silakan refresh halaman.');
            return false;
        }
        
        try {
            await puter.auth.signIn();
            this.isAuthenticated = true;
            this.addSystemMessage('✅ Berhasil login dengan Puter!');
            return true;
        } catch (error) {
            console.error('Authentication error:', error);
            this.addSystemMessage('❌ Gagal login. Silakan coba lagi.');
            return false;
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        
        if (!message || this.isTyping) return;
        
        // Add user message
        this.addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
        
        // Process with AI
        await this.processWithAI(message);
    }
    
    async processWithAI(message) {
        this.showTyping();
        this.isTyping = true;
        
        try {
            // Check if user is asking about events/venues and fetch data
            let eventData = null;
            let venuesData = null;
            
            const lowerMessage = message.toLowerCase();
            
            // Detect intent and fetch relevant data
            if (lowerMessage.includes('event') || lowerMessage.includes('acara') || 
                lowerMessage.includes('tiket') || lowerMessage.includes('konser') ||
                lowerMessage.includes('ada apa') || lowerMessage.includes('what')) {
                eventData = await this.fetchEvents();
            }
            
            if (lowerMessage.includes('venue') || lowerMessage.includes('tempat') || 
                lowerMessage.includes('lokasi')) {
                venuesData = await this.fetchVenues();
            }
            
            // Build context-aware prompt with fetched data
            const context = this.buildContextPrompt(eventData, venuesData);
            const fullPrompt = `${context}\n\nUser: ${message}`;
            
            // Create message element for streaming
            const messageEl = this.addMessage('', 'bot', true);
            this.currentStreamMessage = messageEl;
            
            // Call Puter AI with streaming
            const resp = await puter.ai.chat(fullPrompt, {
                model: 'gpt-4o-mini',
                stream: true,
                temperature: 0.7
            });
            
            let fullResponse = '';
            // Stream response
            for await (const part of resp) {
                if (part?.text) {
                    fullResponse += part.text;
                    this.updateStreamingMessage(messageEl, fullResponse);
                }
            }
            
            // Save to history
            this.saveToHistory(message, fullResponse);
            
        } catch (error) {
            console.error('AI Error:', error);
            this.addMessage('Maaf, terjadi kesalahan. Silakan coba lagi nanti.', 'bot');
        } finally {
            this.hideTyping();
            this.isTyping = false;
            this.currentStreamMessage = null;
        }
    }
    
    async fetchEvents() {
        try {
            const baseUrl = this.config.baseUrl.endsWith('/') ? this.config.baseUrl : this.config.baseUrl + '/';
            const response = await fetch(`${baseUrl}api/chatbot_events.php?action=list&limit=10`);
            const data = await response.json();
            console.log('Fetched events:', data);
            return data.success ? data.data : null;
        } catch (error) {
            console.error('Error fetching events:', error);
            return null;
        }
    }
    
    async fetchVenues() {
        try {
            const baseUrl = this.config.baseUrl.endsWith('/') ? this.config.baseUrl : this.config.baseUrl + '/';
            const response = await fetch(`${baseUrl}api/chatbot_events.php?action=venues`);
            const data = await response.json();
            console.log('Fetched venues:', data);
            return data.success ? data.data : null;
        } catch (error) {
            console.error('Error fetching venues:', error);
            return null;
        }
    }
    
    buildContextPrompt(eventData = null, venuesData = null) {
        const context = [];
        
        context.push('Anda adalah asisten AI untuk MyWisata, platform tiket event di Indonesia.');
        context.push(`Bahasa: ${this.config.language === 'id' ? 'Bahasa Indonesia' : 'English'}`);
        
        if (this.config.isLoggedIn) {
            context.push(`User: ${this.config.userName} (${this.config.isAdmin ? 'Admin' : 'User'})`);
        } else {
            context.push('User: Guest (belum login)');
        }
        
        context.push(`Halaman saat ini: ${this.config.currentPage}`);
        
        // Add specific context based on page
        if (this.config.currentPage === 'events.php') {
            context.push('User sedang melihat halaman daftar event.');
        } else if (this.config.currentPage === 'event_detail.php') {
            context.push('User sedang melihat detail event.');
        } else if (this.config.currentPage === 'index.php') {
            context.push('User di halaman utama.');
        }
        
        // Add event data if available
        if (eventData && eventData.length > 0) {
            context.push('\n=== DATA EVENT TERSEDAILIA ===');
            context.push('Berikut adalah event yang tersedia saat ini:');
            eventData.forEach((event, index) => {
                context.push(`${index + 1}. ${event.name}`);
                context.push(`   - Tanggal: ${event.date}`);
                context.push(`   - Waktu: ${event.time}`);
                context.push(`   - Venue: ${event.venue.name}`);
                context.push(`   - Harga: ${event.price.range}`);
                if (event.description) {
                    context.push(`   - Deskripsi: ${event.description.substring(0, 100)}...`);
                }
                context.push('');
            });
        }
        
        // Add venues data if available
        if (venuesData && venuesData.length > 0) {
            context.push('\n=== DATA VENUE ===');
            context.push('Berikut adalah venue yang tersedia:');
            venuesData.forEach((venue, index) => {
                context.push(`${index + 1}. ${venue.name}`);
                context.push(`   - Alamat: ${venue.address}, ${venue.city}`);
                context.push(`   - Kapasitas: ${venue.capacity} orang`);
                if (venue.phone) context.push(`   - Telepon: ${venue.phone}`);
                context.push('');
            });
        }
        
        context.push('\n=== PETUNJUK ===');
        context.push('Bantu user dengan:');
        context.push('- Informasi event dan tiket berdasarkan data di atas');
        context.push('- Panduan pembelian tiket');
        context.push('- Informasi venue berdasarkan data di atas');
        context.push('- Bantuan umum tentang MyWisata');
        context.push('\nPENTING: Gunakan data yang telah disediakan di atas. JANGAN membuat data atau event fiktif!');
        
        return context.join('\n');
    }
    
    updateStreamingMessage(messageEl, text) {
        const contentEl = messageEl.querySelector('.message-content p');
        if (contentEl) {
            contentEl.innerHTML = this.formatMessage(text);
        }
        // Auto scroll to bottom
        const messagesContainer = document.getElementById('chatbot-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    addMessage(text, sender, isStreaming = false) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}-message`;
        
        const time = new Date().toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        messageDiv.innerHTML = `
            <div class="message-content">
                ${sender === 'bot' && !isStreaming ? `<p>${this.formatMessage(text)}</p>` : 
                  sender === 'bot' && isStreaming ? `<p></p>` : 
                  `<p>${this.escapeHtml(text)}</p>`}
            </div>
            <div class="message-time">${time}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        return messageDiv;
    }
    
    addSystemMessage(text, isHtml = false, customElement = null) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message system-message';
        
        let content = isHtml ? text : this.escapeHtml(text);
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const textP = document.createElement('p');
        textP.innerHTML = content;
        contentDiv.appendChild(textP);
        
        if (customElement) {
            contentDiv.appendChild(customElement);
        }
        
        messageDiv.appendChild(contentDiv);
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = 'Sekarang';
        messageDiv.appendChild(timeDiv);
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    handleQuickAction(action) {
        const actions = {
            events: 'Tampilkan event yang tersedia',
            tickets: this.config.isLoggedIn ? 'Lihat tiket saya' : 'Bagaimana cara membeli tiket?',
            venues: 'Tampilkan venue yang tersedia',
            help: 'Bantuan apa saja yang tersedia?'
        };
        
        const message = actions[action] || action;
        document.getElementById('chatbot-input').value = message;
        this.sendMessage();
    }
    
    showTyping() {
        document.getElementById('chatbot-typing').style.display = 'flex';
        const messagesContainer = document.getElementById('chatbot-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    hideTyping() {
        document.getElementById('chatbot-typing').style.display = 'none';
    }
    
    formatMessage(text) {
        // Convert URLs to links
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
        // Convert line breaks
        text = text.replace(/\n/g, '<br>');
        // Bold text between ** **
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic text between * *
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        return text;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    saveToHistory(userMessage, botResponse) {
        const message = {
            user: userMessage,
            bot: botResponse,
            timestamp: new Date().toISOString()
        };
        
        this.chatHistory.push(message);
        
        // Keep only last 50 messages
        if (this.chatHistory.length > 50) {
            this.chatHistory = this.chatHistory.slice(-50);
        }
        
        // Save to localStorage
        localStorage.setItem('mywisata_chatbot_history', JSON.stringify(this.chatHistory));
        
        // Optionally save to Puter cloud if authenticated
        if (this.isAuthenticated && typeof puter !== 'undefined') {
            try {
                puter.kv.set('chatbot_history', JSON.stringify(this.chatHistory));
            } catch (error) {
                console.error('Error saving to Puter:', error);
            }
        }
    }
    
    loadChatHistory() {
        // Initialize with empty array
        this.chatHistory = [];
        
        // Try localStorage first
        let history = localStorage.getItem('mywisata_chatbot_history');
        if (history) {
            try {
                this.chatHistory = JSON.parse(history);
            } catch (e) {
                console.error('Error parsing chat history:', e);
                this.chatHistory = [];
            }
            return;
        }
        
        // Try Puter cloud if authenticated
        if (this.isAuthenticated && typeof puter !== 'undefined') {
            try {
                puter.kv.get('chatbot_history').then(cloudHistory => {
                    if (cloudHistory) {
                        try {
                            this.chatHistory = JSON.parse(cloudHistory);
                            localStorage.setItem('mywisata_chatbot_history', cloudHistory);
                        } catch (e) {
                            console.error('Error parsing cloud history:', e);
                        }
                    }
                });
            } catch (error) {
                console.error('Error loading from Puter:', error);
            }
        }
    }
    
    clearHistory() {
        this.chatHistory = [];
        localStorage.removeItem('mywisata_chatbot_history');
        
        if (this.isAuthenticated && typeof puter !== 'undefined') {
            try {
                puter.kv.delete('chatbot_history');
            } catch (error) {
                console.error('Error clearing from Puter:', error);
            }
        }
        
        // Clear messages UI
        document.getElementById('chatbot-messages').innerHTML = '';
        this.addSystemMessage('Riwayat chat telah dihapus.');
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit to ensure everything is loaded
    setTimeout(() => {
        // Check if chatbot elements exist
        if (document.getElementById('chatbot-toggle')) {
            try {
                window.myWisataChatbot = new MyWisataChatbot();
                console.log('Chatbot initialized successfully');
            } catch (error) {
                console.error('Error initializing chatbot:', error);
            }
        } else {
            console.warn('Chatbot elements not found in DOM');
        }
    }, 1000);
});

// Make chatbot globally accessible
window.MyWisataChatbot = MyWisataChatbot;
