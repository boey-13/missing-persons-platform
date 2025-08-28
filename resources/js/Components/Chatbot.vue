<template>
    <div>
        <div class="chatbot-btn-container">
            <button class="chatbot-btn" @click="toggleChat" v-if="!isOpen && shouldShowChatbot">
                <svg class="chatbot-icon" width="24" height="24" fill="white" viewBox="0 0 24 24">
                    <path
                        d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12zM7 9h2v2H7V9zm4 0h2v2h-2V9zm4 0h2v2h-2V9z" />
                </svg>
                <div v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</div>
            </button>
        </div>
        <div class="findme-chatbot-modal" v-if="isOpen && shouldShowChatbot">
            <div class="chatbot-header">
                <img src="../assets/chatbot.png" alt="bot" class="header-bot-img" />
                <div class="chatbot-title">
                    <div>FindMe ChatBot</div>
                    <div class="user-status">{{ userStatus }}</div>
                </div>
                <button class="chatbot-close-btn" @click="toggleChat">&times;</button>
            </div>
            <div class="chatbot-body" ref="chatbotBody">
                <div class="chatbot-time">{{ todayTime }}</div>
                <div class="chatbot-messages">
                    <div v-for="(msg, idx) in messages" :key="idx"
                        :class="['msg-bubble', msg.role, { 'menu-bubble': msg.menu, 'typing': msg.typing }]">
                        <span v-if="msg.text">{{ msg.text }}</span>
                        <div v-if="msg.typing" class="typing-indicator">
                            <span></span><span></span><span></span>
                        </div>
                        <div v-if="msg.actionBtn" style="margin-top:8px;">
                            <a :href="msg.actionBtn.url" target="_blank" class="action-btn">
                                {{ msg.actionBtn.label }}
                            </a>
                        </div>
                        <div v-if="msg.menu" class="menu-options">
                            <button v-for="(item, i) in msg.menu" :key="i" class="menu-btn"
                                @click="handleMenuClick(item)">
                                {{ item.label }}
                            </button>
                        </div>
                        <div v-if="msg.searchResults" class="search-results">
                            <div v-for="(result, i) in msg.searchResults" :key="i" class="search-result-item">
                                <div class="result-avatar">
                                    <img v-if="result.photo_url" :src="result.photo_url" alt="Photo" />
                                    <img v-else src="../assets/default-avatar.jpg" alt="Default Avatar" />
                                </div>
                                <div class="result-info">
                                    <div class="result-name">{{ result.full_name }}</div>
                                    <div class="result-details">Age: {{ result.age }} | {{ result.last_seen_location }}</div>
                                </div>
                                <a :href="`/missing-persons/${result.id}`" class="result-link">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chatbot-footer">
                <input class="chatbot-input" v-model="userInput" @keyup.enter="handleInput"
                    placeholder="Type your message..." :disabled="isTyping" />
                <button class="chatbot-send-btn" @click="handleInput" :disabled="isTyping">
                    <svg width="24" height="24" fill="#6C4D3C">
                        <path d="M2 21L23 12 2 3v7l15 2-15 2v7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { usePage } from '@inertiajs/vue3';
import chatbotData from "../config/chatbot.json";

const page = usePage();
const isOpen = ref(false);
const isTyping = ref(false);
const unreadCount = ref(0);
const shouldShowChatbot = computed(() => {
    const path = window.location.pathname;
    return !path.includes("login") && !path.includes("register");
});

const mainMenu = ref(chatbotData.mainMenu);
const faqList = ref(chatbotData.faq);
const userInput = ref("");
const messages = ref([]);
const chatbotBody = ref(null);

// User authentication status
const user = computed(() => page.props.auth?.user);
const userName = computed(() => user.value?.name || "Guest");
const userStatus = computed(() => user.value ? `Welcome, ${userName.value}` : "Guest User");

const todayTime = new Date().toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
});

// Quick commands
const quickCommands = {
    'search': 'searchMissing',
    'report': 'reportMissing',
    'volunteer': 'volunteer',
    'help': 'faq',
    'contact': 'contactSupport',
    'menu': 'mainMenu',
    'home': 'mainMenu'
};

// ---- session helpers (用户隔离的会话管理) ----
const sessionId = ref(null);
const autoEndTimeout = ref(null);
const warningTimeout = ref(null);

// === Unread tracking（只统计system消息，不含user/typing） ===
const lastReadIndex = ref(0);
function countBotMessages(arr = messages.value) {
  return (arr || []).filter(m => m.role !== 'user' && !m.typing).length;
}

function generateSessionId() {
  return 'fm_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

// 获取用户特定的会话键
function getSessionKey() {
  const userId = user.value?.id || 'guest';
  return `findme_chat_session_${userId}`;
}

function storageKey() {
  return getSessionKey();
}

function loadSession() {
  try {
    const raw = localStorage.getItem(storageKey());
    if (!raw) return false;
    const data = JSON.parse(raw) || {};
    if (!data.sessionId) return false;

    // 过滤任何遗留的 typing 泡泡，避免"卡loading"
    messages.value = Array.isArray(data.messages) ? data.messages.filter(m => !m.typing) : [];
    sessionId.value = data.sessionId;
    lastReadIndex.value = data.lastReadIndex || countBotMessages(messages.value);
    if (!isOpen.value) unreadCount.value = Math.max(0, countBotMessages(messages.value) - lastReadIndex.value);

    return true;
  } catch {
    return false;
  }
}

function saveSession() {
  try {
    const payload = {
      sessionId: sessionId.value || generateSessionId(),
      // 永远不保存 typing 项（防止下次打开还在转圈）
      messages: (messages.value || []).filter(m => !m.typing),
      lastReadIndex: lastReadIndex.value,
      unreadCount: !isOpen.value ? Math.max(0, countBotMessages() - lastReadIndex.value) : 0,
    };
    localStorage.setItem(storageKey(), JSON.stringify(payload));
  } catch {}
}

function clearSession() {
  try {
    const sessionKey = getSessionKey();
    localStorage.removeItem(sessionKey);
    sessionId.value = null;
    messages.value = [];
  } catch (e) {
    console.error('Error clearing session:', e);
  }
}

function endChat(clearSessionData = false) {
  if (clearSessionData) {
    clearSession();
  }
  isOpen.value = false;
  unreadCount.value = 0;
  lastReadIndex.value = 0;
}

// 清理所有旧的会话数据（可选的安全措施）
function cleanupOldSessions() {
  try {
    const keys = Object.keys(localStorage);
    const currentSessionKey = getSessionKey();
    const oldSessionKeys = keys.filter(key => 
      key.startsWith('findme_chat_session_') && 
      key !== currentSessionKey
    );
    
    // 清理超过1小时的旧会话
    const oneHourAgo = Date.now() - (60 * 60 * 1000);
    oldSessionKeys.forEach(key => {
      try {
        const data = JSON.parse(localStorage.getItem(key));
        if (data && data.timestamp && data.timestamp < oneHourAgo) {
          localStorage.removeItem(key);
        }
      } catch (e) {
        // 如果数据损坏，直接删除
        localStorage.removeItem(key);
      }
    });
  } catch (e) {
    console.error('Error cleaning up old sessions:', e);
  }
}

function updateActivity() {
  // 清除之前的超时
  if (autoEndTimeout.value) {
    clearTimeout(autoEndTimeout.value);
    autoEndTimeout.value = null;
  }
  if (warningTimeout.value) {
    clearTimeout(warningTimeout.value);
    warningTimeout.value = null;
  }
  
  // 设置30秒警告
  warningTimeout.value = setTimeout(() => {
    messages.value.push({
      role: "system",
      text: "⚠️ You've been inactive for 30 seconds. The chat will end in 1 minute if you don't respond.",
      isWarning: true
    });
    scrollToBottom();
  }, 30 * 1000); // 30秒
  
  // 设置1分30秒自动结束
  autoEndTimeout.value = setTimeout(() => {
    messages.value.push({
      role: "system",
      text: "⏰ Chat ended due to inactivity. You can start a new chat anytime!",
      isEnd: true
    });
    scrollToBottom();
    
    // 2秒后关闭聊天
    setTimeout(() => {
      isOpen.value = false;
      clearSession();
    }, 2000);
  }, 90 * 1000); // 1分30秒
}

function toggleChat() {
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    // 打开：所有当前系统消息都视为已读
    lastReadIndex.value = countBotMessages();
    unreadCount.value = 0;

    const restored = loadSession();
    if (!restored || messages.value.length === 0) {
      sessionId.value = generateSessionId();
      resetChat();
    }
    updateActivity();
    saveSession();
  } else {
    // 关闭：以"当前系统消息数"作为已读基线，避免把刚看过的算未读
    lastReadIndex.value = countBotMessages();
    unreadCount.value = 0;
    saveSession();
  }
}

function resetChat() {
    messages.value = [
        {
            role: "system",
            text: `Hi, ${userName.value}! Welcome to FindMe ChatBot. How can I assist you today? Type 'menu' to see options.`,
        },
        { role: "system", menu: mainMenu.value },
    ];
    userInput.value = "";
}

function scrollToBottom() {
    nextTick(() => {
        if (chatbotBody.value) {
            chatbotBody.value.scrollTop = chatbotBody.value.scrollHeight;
        }
    });
}

let typingToken = 0;

function showTyping() {
  isTyping.value = true;
  messages.value.push({ role: "system", typing: true });
  saveSession();
  scrollToBottom();
}

function hideTyping() {
  isTyping.value = false;
  messages.value = messages.value.filter(m => !m.typing);
  saveSession();
}

async function simulateTyping(callback) {
  const token = ++typingToken;
  showTyping();
  try {
    await new Promise(resolve => setTimeout(resolve, 800 + Math.random() * 900));
    if (token !== typingToken) return; // 有新的typing开始了，当前作废
    hideTyping();
    // callback 可能是 async
    await Promise.resolve(callback());
  } finally {
    saveSession();
  }
}

watch(
  messages,
  (newVal) => {
    const currentBot = countBotMessages(newVal);

    if (isOpen.value) {
      // 打开时：把"最新系统消息数量"记录为已读基线
      lastReadIndex.value = currentBot;
      unreadCount.value = 0;
      saveSession();
      return;
    }

    // 关闭时：只统计新到的系统消息
    unreadCount.value = Math.max(0, currentBot - lastReadIndex.value);
    saveSession();
  },
  { deep: true }
);

watch(messages, scrollToBottom, { deep: true });

async function searchMissingPersons(query) {
    try {
        const response = await fetch(`/api/search-missing-persons?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        return data.results || [];
    } catch (error) {
        console.error('Search error:', error);
        return [];
    }
}

async function handleMenuClick(item) {
    messages.value.push({ role: "user", text: item.label });
    
    // Update activity and save session
    updateActivity();
    saveSession();
    
    await simulateTyping(() => {
        if (item.action === "mainMenu") {
            messages.value.push({
                role: "system",
                text: "Here are the main options:",
            });
            messages.value.push({ role: "system", menu: mainMenu.value });
            return;
        }
        
        if (item.action === "searchDirect" && item.query) {
            handleSearch(item.query);
            return;
        }
        
        if (item.action === "showOtherOptions") {
            messages.value.push({
                role: "system",
                text: "No problem! Here are other ways I can help you:",
            });
            messages.value.push({ role: "system", menu: mainMenu.value });
            return;
        }
        
        if (item.action === "faq") {
            messages.value.push({
                role: "system",
                text: "Please select a FAQ question:",
            });
            messages.value.push({
                role: "system",
                menu: faqList.value.map((faq) => ({
                    label: faq.question,
                    action: "faqAnswer",
                    answer: faq.answer,
                })),
            });
            return;
        }
        
        if (item.action === "faqAnswer") {
            messages.value.push({ role: "system", text: item.answer });
            messages.value.push({
                role: "system",
                text: "Anything else? Type 'menu' for more options.",
            });
            return;
        }
        
        if (item.action === "reportMissing") {
            if (!user.value) {
                messages.value.push({
                    role: "system",
                    text: "You need to log in to report a missing person. Please log in first.",
                    actionBtn: {
                        label: "Go to Login",
                        url: "/login"
                    }
                });
            } else {
                messages.value.push({
                    role: "system",
                    text: "To report a missing person, please use the main report form.",
                    actionBtn: {
                        label: "Go to Report Page",
                        url: "/missing-persons/report"
                    }
                });
            }
            messages.value.push({
                role: "system",
                text: "Anything else? Type 'menu' for more options."
            });
            return;
        }
        
        if (item.action === "searchFilter") {
            messages.value.push({
                role: "system",
                text: "You can search for missing persons by typing 'search [name]' or 'search [location]'. You can also visit the main search page.",
            });
            messages.value.push({
                role: "system",
                text: "You can also visit the main search page for advanced filtering options.",
                actionBtn: {
                    label: "Go to Search Page",
                    url: "/missing-persons"
                }
            });
        } else if (item.action === "volunteer") {
            if (!user.value) {
                messages.value.push({
                    role: "system",
                    text: "You need to log in to become a volunteer. Please log in first.",
                    actionBtn: {
                        label: "Go to Login",
                        url: "/login"
                    }
                });
            } else {
                messages.value.push({
                    role: "system",
                    text: "Become a Volunteer by submitting an application.",
                    actionBtn: { 
                        label: 'Go to Volunteer Application', 
                        url: '/volunteer/apply' 
                    }
                });
            }
        } else if (item.action === "contactSupport") {
            messages.value.push({
                role: "system",
                text: "Contact us at support@findme.com or call us at 011-11223344. You can also leave a message here and we'll get back to you.",
            });
        } else if (item.action === "endChat") {
            messages.value.push({
                role: "system",
                text: "👋 Thank you for using FindMe ChatBot! Have a great day!",
                isEnd: true
            });
            
            // 2秒后关闭聊天并清除会话
            setTimeout(() => {
                isOpen.value = false;
                clearSession();
            }, 2000);
            return; // 不显示"Anything else?"消息
        } else {
            messages.value.push({ role: "system", text: "This feature is coming soon!" });
        }
        
        messages.value.push({
            role: "system",
            text: "Anything else? Type 'menu' for more options.",
        });
    });
}

async function handleInput() {
    if (!userInput.value.trim() || isTyping.value) return;
    
    const input = userInput.value.trim().toLowerCase();
    messages.value.push({ role: "user", text: userInput.value.trim() });
    
    // Update activity and save session
    updateActivity();
    saveSession();
    
    await simulateTyping(() => {
        // Check for search queries FIRST (highest priority)
        if (input.startsWith('search ')) {
            const searchQuery = userInput.value.trim().substring(7);
            handleSearch(searchQuery);
            userInput.value = "";
            return;
        }
        
        // Check for direct search patterns
        if (input.includes('find') || input.includes('look for') || input.includes('search for')) {
            const searchTerms = input.replace(/(find|look for|search for)/g, '').trim();
            if (searchTerms) {
                handleSearch(searchTerms);
                userInput.value = "";
                return;
            }
        }
        
        // Check for quick commands (but exclude 'search' to avoid conflict)
        for (const [command, action] of Object.entries(quickCommands)) {
            if (command !== 'search' && input.includes(command)) {
                handleQuickCommand(action);
                userInput.value = "";
                return;
            }
        }
        
        // Check for specific questions
        if (input.includes('how') || input.includes('what') || input.includes('where') || input.includes('when')) {
            handleQuestion(userInput.value.trim());
            userInput.value = "";
            return;
        }
        
        // Check if input might be a search query (names, locations, etc.)
        if (input.length > 2 && !input.includes('help') && !input.includes('menu')) {
            // Ask user if they want to search
            messages.value.push({
                role: "system",
                text: `Did you want to search for "${userInput.value.trim()}"? I can help you find missing persons by name, location, or other details.`,
                menu: [
                    { label: `🔍 Search for "${userInput.value.trim()}"`, action: "searchDirect", query: userInput.value.trim() },
                    { label: "❌ No, show me other options", action: "showOtherOptions" }
                ]
            });
            userInput.value = "";
            return;
        }
        
        // Default response
        messages.value.push({
            role: "system",
            text: "I'm not sure how to help with that. Type 'menu' to see options, or try 'search [name]' to find missing persons.",
        });
    });
    
    userInput.value = "";
}

function handleQuickCommand(action) {
    const actionMap = {
        'searchMissing': () => {
            messages.value.push({
                role: "system",
                text: "You can search for missing persons by typing 'search [name]' or 'search [location]'. You can also visit the main search page.",
                actionBtn: {
                    label: "Go to Search Page",
                    url: "/missing-persons"
                }
            });
        },
        'reportMissing': () => {
            if (!user.value) {
                messages.value.push({
                    role: "system",
                    text: "You need to log in to report a missing person.",
                    actionBtn: {
                        label: "Go to Login",
                        url: "/login"
                    }
                });
            } else {
                messages.value.push({
                    role: "system",
                    text: "To report a missing person, please use the main report form.",
                    actionBtn: {
                        label: "Go to Report Page",
                        url: "/missing-persons/report"
                    }
                });
            }
        },
        'volunteer': () => {
            if (!user.value) {
                messages.value.push({
                    role: "system",
                    text: "You need to log in to become a volunteer.",
                    actionBtn: {
                        label: "Go to Login",
                        url: "/login"
                    }
                });
            } else {
                messages.value.push({
                    role: "system",
                    text: "Become a Volunteer by submitting an application.",
                    actionBtn: { 
                        label: 'Go to Volunteer Application', 
                        url: '/volunteer/apply' 
                    }
                });
            }
        },
        'faq': () => {
            messages.value.push({
                role: "system",
                text: "Please select a FAQ question:",
            });
            messages.value.push({
                role: "system",
                menu: faqList.value.map((faq) => ({
                    label: faq.question,
                    action: "faqAnswer",
                    answer: faq.answer,
                })),
            });
        },
        'contactSupport': () => {
            messages.value.push({
                role: "system",
                text: "Contact us at support@findme.com or call us at 011-11223344.",
            });
        },
        'mainMenu': () => {
            messages.value.push({
                role: "system",
                text: "Here are the main options:",
            });
            messages.value.push({ role: "system", menu: mainMenu.value });
        }
    };
    
    if (actionMap[action]) {
        actionMap[action]();
    }
}

async function handleSearch(query) {
  if (!query.trim()) {
    messages.value.push({
      role: "system",
      text: "Please provide a search term. Try 'search [name]' or 'search [location]'.",
    });
    saveSession();
    return;
  }

  messages.value.push({ role: "system", text: `Searching for: "${query}"...` });
  saveSession();

  try {
    const results = await searchMissingPersons(query);

    if (results.length > 0) {
      messages.value.push({
        role: "system",
        text: `Found ${results.length} result(s):`,
        searchResults: results.slice(0, 5)
      });
    } else {
      messages.value.push({
        role: "system",
        text: `No results found for "${query}". Try a different search term or visit the main search page for more options.`,
        actionBtn: { label: "Go to Search Page", url: "/missing-persons" }
      });
    }
  } catch (e) {
    messages.value.push({
      role: "system",
      text: "Search failed. Please try again in a moment."
    });
  } finally {
    saveSession();
  }
}

function handleQuestion(question) {
    const lowerQuestion = question.toLowerCase();
    
    if (lowerQuestion.includes('login') || lowerQuestion.includes('register')) {
        messages.value.push({
            role: "system",
            text: "You can log in or register using the links in the top navigation bar.",
            actionBtn: {
                label: "Go to Login",
                url: "/login"
            }
        });
    } else if (lowerQuestion.includes('points') || lowerQuestion.includes('reward')) {
        messages.value.push({
            role: "system",
            text: "You can earn points by reporting missing persons, submitting sighting reports, sharing cases on social media, and participating in community projects. Visit the rewards page to see available rewards.",
            actionBtn: {
                label: "Go to Rewards",
                url: "/rewards"
            }
        });
    } else if (lowerQuestion.includes('contact') || lowerQuestion.includes('support')) {
        messages.value.push({
            role: "system",
            text: "Contact us at support@findme.com or call us at 011-11223344. Our support team is available 24/7.",
        });
    } else {
        messages.value.push({
            role: "system",
            text: "I'm not sure about that. Try asking a specific question or type 'menu' to see available options.",
        });
    }
}

// Auto-show chatbot after 30 seconds if user hasn't interacted
onMounted(() => {
    // Load existing session on mount
    const restored = loadSession();
    
    // If no session was restored and no messages exist, initialize a new chat
    if (!restored && messages.value.length === 0) {
        sessionId.value = generateSessionId();
        resetChat();
        saveSession();
    }
    
    // Save session before page unload
    window.addEventListener('beforeunload', () => {
        if (isOpen.value && messages.value.length > 0) {
            saveSession();
        }
    });
    
    // Save session when page visibility changes (user switches tabs)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && isOpen.value && messages.value.length > 0) {
            saveSession();
        }
    });
    
    setTimeout(() => {
        if (!isOpen.value && shouldShowChatbot.value) {
            unreadCount.value = 1;
        }
    }, 30000);
});
</script>

<style scoped>
.chatbot-btn-container {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1000;
}

.chatbot-btn {
    width: 56px;
    height: 56px;
    background: #5C4033;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.chatbot-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.chatbot-icon {
    width: 24px;
    height: 24px;
}

.action-btn {
  display: inline-block;
  background: #F4EFE7;
  color: #6C4D3C;
  padding: 10px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  margin: 8px 0 2px 0;
  text-decoration: none;
  border: 1.5px solid #c5b9ab;
  box-shadow: 0 2px 8px rgba(111,79,53,0.10);
  transition: background 0.16s, color 0.18s, box-shadow 0.2s;
  letter-spacing: 0.3px;
}
.action-btn:hover {
  background: #EFE0C9;
  color: #51321E;
  border-color: #6C4D3C;
  box-shadow: 0 5px 16px rgba(180,144,104,0.16);
}

.findme-chatbot-modal {
    position: fixed;
    right: 20px;
    bottom: 90px;
    width: 380px;
    max-height: 600px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    border: 1px solid #e5e5e5;
}

.chatbot-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #000000;
    background: #483d3d;
    color: white;
    border-radius: 16px 16px 0 0;
}

.header-bot-img {
    width: 36px;
    height: 36px;
    margin-right: 8px;
    border-radius: 50%;
}

.chatbot-title {
    font-size: 16px;
    font-weight: 600;
    flex: 1;
}

.user-status {
    font-size: 12px;
    opacity: 0.8;
    margin-top: 2px;
}

.chatbot-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: white;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.chatbot-close-btn:hover {
    opacity: 1;
}

.chatbot-body {
    flex: 1;
    background: #f9fafb;
    padding: 12px;
    overflow-y: auto;
    max-height: 400px;
}

.chatbot-time {
    font-size: 12px;
    color: #888;
    text-align: center;
    margin-bottom: 8px;
}

.chatbot-messages {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.msg-bubble {
    font-size: 14px;
    padding: 8px 12px;
    margin: 4px 8px;
    border-radius: 12px;
    max-width: 85%;
    line-height: 1.4;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.msg-bubble.system {
    background: #e0f2fe;
    color: #1e40af;
}

.msg-bubble.user {
    background: #bfdbfe;
    color: #1e3a8a;
    margin-left: auto;
}

.msg-bubble.faq {
    background: #fef3c7;
    color: #92400e;
    font-weight: 500;
}

.menu-bubble {
    background: #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px;
}

.menu-options {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.menu-btn {
    background: #ffffff;
    color: #333;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
}

.menu-btn:hover {
    background: #e2e8f0;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 8px 0;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #9ca3af;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

.search-results {
    margin-top: 8px;
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 8px;
    background: white;
    border-radius: 8px;
    margin-bottom: 6px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.search-result-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
}

.result-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 12px;
    flex-shrink: 0;
}

.result-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.result-info {
    flex: 1;
    min-width: 0;
}

.result-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2px;
}

.result-details {
    font-size: 12px;
    color: #6b7280;
}

.result-link {
    background: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: background 0.2s;
}

.result-link:hover {
    background: #2563eb;
}

.chatbot-footer {
    display: flex;
    align-items: center;
    padding: 12px;
    border-top: 1px solid #e5e5e5;
    background: #ffffff;
    border-radius: 0 0 16px 16px;
}

.chatbot-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.chatbot-input:focus {
    border-color: #3b82f6;
}

.chatbot-input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
}

.chatbot-send-btn {
    background: none;
    border: none;
    padding: 0 8px;
    cursor: pointer;
    transition: transform 0.2s;
}

.chatbot-send-btn:hover:not(:disabled) {
    transform: scale(1.1);
}

.chatbot-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>