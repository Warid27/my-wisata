<!-- Chatbot Widget -->
<div id="chatbot-widget" class="chatbot-widget">
    <!-- Chat Toggle Button -->
    <button id="chatbot-toggle" class="chatbot-toggle-btn" title="Buka Asisten AI">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="chatbot-badge">AI</span>
    </button>
    
    <!-- Chat Container -->
    <div id="chatbot-container" class="chatbot-container">
        <!-- Chat Header -->
        <div class="chatbot-header">
            <div class="d-flex align-items-center">
                <div class="chatbot-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="ms-2">
                    <h6 class="mb-0">Asisten MyWisata</h6>
                    <small class="text-success">
                        <i class="bi bi-circle-fill"></i> Online
                    </small>
                </div>
            </div>
            <button id="chatbot-close" class="btn btn-sm btn-link text-white">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div id="chatbot-messages" class="chatbot-messages">
            <div class="chatbot-message bot-message">
                <div class="message-content">
                    <p>Halo! 👋 Saya adalah asisten AI MyWisata. Saya bisa membantu Anda:</p>
                    <ul>
                        <li>Mencari informasi event</li>
                        <li>Membantu pembelian tiket</li>
                        <li>Informasi venue</li>
                        <li>Pertanyaan umum tentang MyWisata</li>
                    </ul>
                    <p>Apa yang bisa saya bantu hari ini?</p>
                </div>
                <div class="message-time">Sekarang</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="chatbot-quick-actions">
            <button class="quick-action-btn" data-action="events">
                <i class="bi bi-calendar-event"></i> Event
            </button>
            <button class="quick-action-btn" data-action="tickets">
                <i class="bi bi-ticket-fill"></i> Tiket Saya
            </button>
            <button class="quick-action-btn" data-action="venues">
                <i class="bi bi-geo-alt-fill"></i> Venue
            </button>
            <button class="quick-action-btn" data-action="help">
                <i class="bi bi-question-circle"></i> Bantuan
            </button>
        </div>
        
        <!-- Chat Input -->
        <div class="chatbot-input-container">
            <div class="input-group">
                <input type="text" id="chatbot-input" class="form-control" 
                       placeholder="Ketik pesan Anda..." maxlength="500">
                <button class="btn btn-primary" id="chatbot-send" type="button">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div class="chatbot-input-footer">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> Pesan Anda aman & terenkripsi
                </small>
            </div>
        </div>
        
        <!-- Typing Indicator -->
        <div id="chatbot-typing" class="chatbot-typing" style="display: none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <small>Asisten sedang mengetik...</small>
        </div>
    </div>
</div>

<!-- Chatbot Configuration Script -->
<script>
window.ChatbotConfig = {
    baseUrl: '<?php echo base_url(); ?>',
    assetsUrl: '<?php echo assets_url(); ?>',
    isLoggedIn: <?php echo is_logged_in() ? 'true' : 'false'; ?>,
    userName: '<?php echo is_logged_in() ? get_user_name() : ''; ?>',
    isAdmin: <?php echo is_admin() ? 'true' : 'false'; ?>,
    currentPage: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
    language: 'id',
    puterAuthRequired: false
};
</script>
