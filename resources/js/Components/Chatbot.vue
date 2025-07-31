<template>
    <div>
        <button class="chatbot-btn" @click="toggleChat" v-if="!isOpen && shouldShowChatbot">
            <svg class="chatbot-icon" width="24" height="24" fill="white" viewBox="0 0 24 24">
                <path
                    d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12zM7 9h2v2H7V9zm4 0h2v2h-2V9zm4 0h2v2h-2V9z" />
            </svg>
        </button>
        <div class="findme-chatbot-modal" v-if="isOpen && shouldShowChatbot">
            <div class="chatbot-header">
                <img src="../assets/chatbot.png" alt="bot" class="header-bot-img" />
                <div class="chatbot-title">FindMe ChatBot</div>
                <button class="chatbot-close-btn" @click="toggleChat">&times;</button>
            </div>
            <div class="chatbot-body" ref="chatbotBody">
                <div class="chatbot-time">{{ todayTime }}</div>
                <div class="chatbot-messages">
                    <div v-for="(msg, idx) in messages" :key="idx"
                        :class="['msg-bubble', msg.role, { 'menu-bubble': msg.menu }]">
                        <span v-if="msg.text">{{ msg.text }}</span>
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
                    </div>

                </div>
            </div>
            <div class="chatbot-footer">
                <input class="chatbot-input" v-model="userInput" @keyup.enter="handleInput"
                    placeholder="Type your message..." />
                <button class="chatbot-send-btn" @click="handleInput">
                    <svg width="24" height="24" fill="#6C4D3C">
                        <path d="M2 21L23 12 2 3v7l15 2-15 2v7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from "vue";
import chatbotData from "../config/chatbot.json";

const isOpen = ref(false);
const userName = ref("Guest");
const shouldShowChatbot = computed(() => {
    const path = window.location.pathname;
    return !path.includes("login") && !path.includes("register");
});
const mainMenu = ref(chatbotData.mainMenu);
const faqList = ref(chatbotData.faq);
const userInput = ref("");
const messages = ref([]);
const chatbotBody = ref(null);
const todayTime = new Date().toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
});

function toggleChat() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        resetChat();
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

watch(messages, scrollToBottom, { deep: true });

function handleMenuClick(item) {
    messages.value.push({ role: "user", text: item.label });
    if (item.action === "mainMenu") {
        messages.value.push({
            role: "system",
            text: "Here are the main options:",
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
        messages.value.push({
            role: "system",
            text: "To report a missing person, please use the main report form.\n",
            actionBtn: {
                label: "Go to Report Page",
                url: "/missing-persons/report"
            }
        });
        messages.value.push({
            role: "system",
            text: "Anything else? Type 'menu' for more options."
        });
        return;

    } else if (item.action === "searchFilter") {
        messages.value.push({
            role: "system",
            text: "Search by name or filter by age, gender, or last seen location.",
        });
    } else if (item.action === "volunteer") {
        messages.value.push({
            role: "system",
            text: "Please log in to become a volunteer. [Login Button]",
        });
    } else if (item.action === "contactSupport") {
        messages.value.push({
            role: "system",
            text: "Contact us at support@email.com or type your message here.",
        });
    } else {
        messages.value.push({ role: "system", text: "This feature is coming soon!" });
    }
    messages.value.push({
        role: "system",
        text: "Anything else? Type 'menu' for more options.",
    });
}

function handleInput() {
    if (!userInput.value.trim()) return;
    messages.value.push({ role: "user", text: userInput.value.trim() });
    if (["menu", "home"].includes(userInput.value.toLowerCase().trim())) {
        messages.value.push({
            role: "system",
            text: "Here are the main options:",
        });
        messages.value.push({ role: "system", menu: mainMenu.value });
    } else {
        messages.value.push({
            role: "system",
            text: "I'm not sure how to help with that. Type 'menu' to see options.",
        });
    }
    userInput.value = "";
}
</script>

<style scoped>
.chatbot-btn {
    position: fixed;
    right: 24px;
    bottom: 24px;
    width: 56px;
    height: 56px;
    background: #5C4033;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s, box-shadow 0.2s;
}

.chatbot-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
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
    width: 340px;
    max-height: 500px;
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
    border-bottom: 1px solid #e5e5e5;
}

.header-bot-img {
    width: 36px;
    height: 36px;
    margin-right: 8px;
}

.chatbot-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    flex: 1;
}

.chatbot-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    cursor: pointer;
}

.chatbot-body {
    flex: 1;
    background: #f9fafb;
    padding: 12px;
    overflow-y: auto;
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
    max-width: 80%;
    line-height: 1.4;
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
    padding: 8px;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
    transition: background 0.2s;
}

.menu-btn:hover {
    background: #e2e8f0;
}

.chatbot-footer {
    display: flex;
    align-items: center;
    padding: 12px;
    border-top: 1px solid #e5e5e5;
    background: #ffffff;
}

.chatbot-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    outline: none;
}

.chatbot-send-btn {
    background: none;
    border: none;
    padding: 0 8px;
    cursor: pointer;
}
</style>