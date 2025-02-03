<?php
// employee/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_login();

// If you have a DB connection
require_once __DIR__ . '/../config/database.php';  

// Optionally ensure user_role is 'employee' or 'staff':
// if (get_logged_in_user_role() !== 'employee') { ... }

############################
# LOAD CHANNELS FROM DB
############################
$sql = "SELECT channel_id, channel_name FROM chat_channels ORDER BY channel_id ASC";
$res = $conn->query($sql);
$channels = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gommer Patton Law - Employee Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Scrollbars */
    ::-webkit-scrollbar { width:8px; }
    ::-webkit-scrollbar-thumb { background-color:#cbd5e1; border-radius:4px; }
    .slide-up { transition: max-height 0.3s ease; }
    body { font-size:0.95rem; }
    .message-timestamp { font-size:0.75rem; color:#a1a1aa; margin-left:0.5rem; }
    @media (max-width:768px) { #voPanel { width:95%; max-width:none; } }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">

<!-- TOP NAV -->
<header class="bg-[#0C1D36] text-white">
  <div class="max-w-screen-2xl mx-auto px-4 py-3 flex items-center justify-between">
    <!-- Logo / Brand -->
    <div class="flex items-center space-x-3">
      <img src="https://via.placeholder.com/40?text=Logo" alt="Firm Logo" class="w-10 h-10"/>
      <span class="text-xl font-bold">Gommer Patton Law</span>
    </div>
    <!-- Logout -->
    <a href="/logout.php" class="bg-[#C6A300] hover:bg-yellow-600 px-3 py-2 rounded-md text-sm">
      Logout
    </a>
  </div>
</header>

  <!-- LAYOUT WRAPPER (SIDEBAR + CONTENT) -->
  <div class="flex max-w-screen-2xl mx-auto">

    <!-- SIDEBAR (FIXED, NO SLIDE) -->
    <aside class="w-64 bg-[#0C1D36] text-white flex-shrink-0 hidden md:block">
      <div class="h-full flex flex-col">
        <!-- SIDEBAR HEADER / TITLE -->
        <div class="border-b border-gray-700 p-4">
          <h2 class="text-lg font-semibold">Main Menu</h2>
        </div>
        <!-- NAVIGATION MENU -->
        <nav class="flex-1 overflow-y-auto text-sm">
          <ul>
            <!-- 1. Dashboard -->
            <li class="border-b border-gray-700">
              <a href="#home" class="block px-6 py-3 hover:bg-[#11294a]">Dashboard (Home)</a>
            </li>
            <!-- 2. Firm Cases (Some roles locked out) -->
            <li class="border-b border-gray-700">
              <a href="#" class="block px-6 py-3 hover:bg-[#11294a] flex items-center justify-between">
                <span>Case Management</span>
                <!-- Suppose Admin or Paralegal can see some parts locked if not assigned attorney -->
                <span class="text-red-300 text-xs ml-2">🔒</span>
              </a>
            </li>
            <!-- 3. Document Library (Locked) -->
            <li class="border-b border-gray-700">
              <a href="#" class="block px-6 py-3 hover:bg-[#11294a] flex items-center justify-between">
                <span>Document Library</span>
                <span class="text-red-300 text-xs ml-2">🔒</span>
              </a>
            </li>
            <!-- 4. Time Tracking & Billing (Attorney/Paralegal mostly) -->
            <li class="border-b border-gray-700">
              <a href="#" class="block px-6 py-3 hover:bg-[#11294a]">Billing &amp; Time Tracking</a>
            </li>
          <li class="border-b border-gray-700">
            <button 
              class="w-full text-left px-6 py-3 hover:bg-[#11294a] flex items-center justify-between"
              onclick="toggleHRSection()"
            >
              <span>HR &amp; Payroll</span>
              <span class="text-sm">▼</span>
            </button>
            <ul id="hrDropdown" class="hidden bg-[#0C1D36] text-white">
              <li>
                <a href="#" 
                   class="block px-8 py-2 hover:bg-[#11294a]"
                   onclick="openEquipModal()"
                >
                  Request Equipment
                </a>
              </li>
              <li>
                <a href="#" class="block px-8 py-2 hover:bg-[#11294a]">View Pay Stubs</a>
              </li>
            </ul>
          </li>
          <li class="border-b border-gray-700">
            <a href="#" class="block px-6 py-3 hover:bg-[#11294a]">Training & Orientation</a>
          </li>
          <li class="border-b border-gray-700">
            <a href="#" class="block px-6 py-3 hover:bg-[#11294a]">Firm Directory</a>
          </li>
          <li class="border-b border-gray-700">
            <a href="#" class="block px-6 py-3 hover:bg-[#11294a]">Remote Resources</a>
          </li>
          <li>
            <a href="#" class="block px-6 py-3 hover:bg-[#11294a]">Support Center</a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- MAIN CONTENT AREA -->
  <main class="flex-1 p-4 md:p-6" id="home">
    <!-- WELCOME SECTION -->
    <section class="mb-8 bg-white rounded-lg shadow p-4">
      <h1 class="text-2xl font-bold text-[#0C1D36] mb-2">
        Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>
      </h1>
      <p class="text-gray-500 text-sm">
        Your Role: <?= htmlspecialchars($_SESSION['user_role'] ?? 'Employee'); ?>
      </p>
      <blockquote class="text-gray-600 italic mt-2">
        “Law is not law, if it violates the principles of eternal justice.” – Lydia Maria Child
      </blockquote>
    </section>

    <!-- TASKS & ANNOUNCEMENTS -->
      <section class="grid md:grid-cols-2 gap-6 mb-8">
        <!-- Primary Tasks (Role-Specific) -->
        <div class="bg-white rounded-lg shadow p-4">
          <h2 class="text-xl font-semibold text-[#0C1D36] mb-3">Priority Tasks</h2>
          <p class="text-sm text-gray-600 mb-3">Below are tasks relevant to your current role and onboarding progress.</p>
          <ul class="space-y-3 text-sm">
            <li class="flex items-center justify-between border p-3 rounded">
              <div>
                <p class="font-medium">Complete E-Sign on Confidentiality Agreement</p>
                <p class="text-gray-500 text-xs">Required before accessing client data</p>
              </div>
              <span class="text-orange-500 text-xs font-medium">Pending</span>
            </li>
            <li class="flex items-center justify-between border p-3 rounded">
              <div>
                <p class="font-medium">Review New Remote Work Policy</p>
                <p class="text-gray-500 text-xs">Firm-wide mandatory read</p>
              </div>
              <span class="text-blue-600 text-xs font-medium">In Progress</span>
            </li>
            <li class="flex items-center justify-between border p-3 rounded">
              <div>
                <p class="font-medium">Attend Orientation Webinar</p>
                <p class="text-gray-500 text-xs">Dates: Jan 15 or Jan 20</p>
              </div>
              <span class="text-green-600 text-xs font-medium">Scheduled</span>
            </li>
            <li class="flex items-center justify-between border p-3 rounded">
              <div>
                <p class="font-medium">Access Billing Module</p>
                <p class="text-gray-500 text-xs">Role-based permission required</p>
              </div>
              <span class="text-red-400 text-xs flex items-center font-medium">
                🔒 Locked
              </span>
            </li>
          </ul>
        </div>
        
        <!-- Firm Announcements -->
        <div class="bg-white rounded-lg shadow p-4">
          <h2 class="text-xl font-semibold text-[#0C1D36] mb-3">Firm Announcements</h2>
          <ul class="list-disc list-inside text-sm text-gray-600 space-y-2">
            <li><strong>Quarterly Town Hall:</strong> Jan 25th, covering firm updates and policy changes.</li>
            <li><strong>New Data Security Initiative:</strong> Updated protocols for remote staff. Email to follow.</li>
            <li><strong>Attorney Spotlight:</strong> John Patton recognized as a “Top 50 litigator” in the region.</li>
            <li><strong>Mental Health Resources:</strong> EAP line and wellness sessions available every Friday.</li>
          </ul>
        </div>
      </section>

      <!-- ONBOARDING / LOCKED AREAS & OPTIONAL METRICS -->
      <section class="grid md:grid-cols-2 gap-6">
        <!-- Onboarding Progress (Generic For All New Employees) -->
        <div class="bg-white rounded-lg shadow p-4">
          <h2 class="text-xl font-semibold text-[#0C1D36] mb-2">Onboarding Progress</h2>
          <label class="block mb-1 font-medium text-sm">Overall Onboarding: 40%</label>
          <div class="w-full bg-gray-300 h-3 rounded-full relative overflow-hidden mb-4">
            <div class="bg-[#C6A300] h-full w-[40%]"></div>
          </div>
          <p class="text-sm text-gray-600 mb-2">As you complete required tasks, more features unlock automatically.</p>
          <ul class="list-disc list-inside text-sm text-gray-500 space-y-1">
            <li>Firm Directory Access: <span class="text-blue-800 font-medium">Enabled</span></li>
            <li>Client Contact Info: <span class="text-red-500 font-medium">Locked</span></li>
            <li>Document Upload Permissions: <span class="text-orange-600 font-medium">Pending Manager Approval</span></li>
          </ul>
        </div>

        <!-- Some Quick Metrics or Info (For Attorneys, Admin, or Paralegal) -->
        <div class="bg-gray-100 border-l-4 border-gray-400 p-4 rounded">
          <strong class="block text-gray-700 mb-1">Locked / Role-Specific Sections:</strong>
          <p class="text-gray-600 text-sm mb-2">
            Due to your current access level, certain features remain unavailable:
          </p>
          <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
            <li>Advanced Case Management <span class="text-red-400 text-xs">🔒</span></li>
            <li>Billing &amp; Invoicing Tools <span class="text-red-400 text-xs">🔒</span></li>
            <li>High-Sensitivity Client Files <span class="text-red-400 text-xs">🔒</span></li>
          </ul>
        </div>
      </section>
    </main>
  </div>

<!-- EQUIPMENT REQUEST MODAL -->
<div id="equipModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
    <h3 class="text-xl font-semibold mb-4">Request Equipment</h3>
    <form id="equipRequestForm">
      <label class="block mb-2">
        <span class="text-gray-700">Equipment Type</span>
        <select name="request_type" class="border border-gray-300 rounded w-full p-2" required>
          <option value="">-- Select --</option>
          <option value="Laptop">Laptop</option>
          <option value="Ergonomic Chair">Ergonomic Chair</option>
          <option value="Headset">Headset</option>
          <option value="Software License">Software License</option>
        </select>
      </label>
      <label class="block mb-2">
        <span class="text-gray-700">Additional Details</span>
        <textarea name="request_details" class="border border-gray-300 rounded w-full p-2" rows="3"
          placeholder="Specify model preferences, special requirements, etc."
        ></textarea>
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded"
                onclick="closeEquipModal()">
          Cancel
        </button>
        <button type="submit" class="bg-[#0C1D36] hover:bg-[#11294a] text-white px-4 py-2 rounded">
          Submit
        </button>
      </div>
    </form>
  </div>
</div>

<!-- VIRTUAL OFFICE CHAT BUTTON -->
<button id="voButton"
  class="fixed bottom-4 right-4 bg-[#C6A300] text-white px-4 py-2 rounded-full shadow-lg hover:bg-yellow-600 transition z-50">
  Virtual Office
</button>

<!-- VIRTUAL OFFICE CHAT PANEL -->
<div id="voPanel"
  class="fixed bottom-4 right-4 w-[90%] max-w-3xl bg-white rounded-lg shadow-lg slide-up max-h-0 flex flex-col overflow-hidden"
  style="z-index:100;"
>
  <div class="bg-[#0C1D36] text-white px-4 py-2 flex items-center justify-between">
    <h3 class="font-semibold text-lg">Virtual Office</h3>
    <button id="closeVoPanel" class="text-white text-2xl leading-none">&times;</button>
  </div>
  <div class="flex-1 flex flex-col sm:flex-row overflow-hidden" style="min-height:300px;">
    <!-- Channel List -->
    <div class="bg-gray-200 w-full sm:w-1/5 border-r border-gray-300">
      <h4 class="px-3 py-2 text-xs font-bold text-gray-700 uppercase">Channels</h4>
      <ul id="channelList" class="overflow-y-auto text-sm">
        <?php if ($channels): ?>
          <?php foreach($channels as $i=>$ch): ?>
            <li class="px-3 py-2 hover:bg-gray-300 cursor-pointer font-medium <?= ($i===0?'bg-gray-300':'') ?>"
              data-channel-id="<?= $ch['channel_id'] ?>"
              data-channel-name="<?= htmlspecialchars($ch['channel_name']) ?>"
            >
              <?= htmlspecialchars($ch['channel_name']) ?>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="px-3 py-2 text-gray-600">No channels found</li>
        <?php endif; ?>
      </ul>
    </div>
    <!-- Chat area -->
    <div class="flex-1 flex flex-col" id="chatArea">
      <div class="border-b border-gray-200 px-4 py-2">
        <h4 class="font-semibold text-gray-700" id="channelTitle">
          #<?= htmlspecialchars($channels[0]['channel_name'] ?? 'general') ?>
        </h4>
      </div>
      <div class="flex-1 overflow-y-auto p-3 text-sm" id="chatMessages"></div>
      <div id="typingIndicators" class="px-3 py-1 text-xs text-gray-500 italic"></div>
      <div class="border-t border-gray-200 p-2 flex items-center gap-2">
        <input type="text" id="chatInput"
          class="flex-1 border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#C6A300]"
          placeholder="Message #<?= htmlspecialchars($channels[0]['channel_name'] ?? 'general') ?>"
        >
        <button id="chatSend" class="bg-[#0C1D36] text-white px-4 py-1 rounded hover:bg-[#11294a] transition">
          Send
        </button>
      </div>
    </div>
    <!-- User List -->
    <div class="hidden sm:block w-full sm:w-1/5 border-l border-gray-300 bg-gray-200">
      <h4 class="px-3 py-2 text-xs font-bold text-gray-700 uppercase">Online</h4>
      <ul id="userList" class="text-sm text-gray-800"></ul>
    </div>
  </div>
</div>

<script>
// HR & Payroll dropdown
function toggleHRSection() {
  const dd = document.getElementById('hrDropdown');
  dd.classList.toggle('hidden');
}
function openEquipModal() {
  document.getElementById('equipModal').classList.remove('hidden');
}
function closeEquipModal() {
  document.getElementById('equipModal').classList.add('hidden');
}

// Equipment Request
const equipForm = document.getElementById('equipRequestForm');
equipForm.addEventListener('submit', e=>{
  e.preventDefault();
  const formData = new FormData(equipForm);
  fetch('/employee/request_check.php',{
    method:'POST',
    body: formData
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      alert('Request submitted successfully.');
      closeEquipModal();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err=>console.error('Request equipment error:', err));
});

// Virtual Office chat toggle
const voButton    = document.getElementById('voButton');
const voPanel     = document.getElementById('voPanel');
const closeVoPanel= document.getElementById('closeVoPanel');
let isPanelOpen   = false;

function toggleVoPanel(){
  isPanelOpen = !isPanelOpen;
  voPanel.style.maxHeight = isPanelOpen ? '600px' : '0px';
}
voButton.addEventListener('click', toggleVoPanel);
closeVoPanel.addEventListener('click', toggleVoPanel);

// Chat logic
const channelList = document.getElementById('channelList');
const channelTitle= document.getElementById('channelTitle');
const chatMessages= document.getElementById('chatMessages');
const chatInput   = document.getElementById('chatInput');
const chatSend    = document.getElementById('chatSend');
const typingIndicators = document.getElementById('typingIndicators');
const userList    = document.getElementById('userList');

let currentChannelId = <?= !empty($channels) ? (int)$channels[0]['channel_id'] : 1 ?>;
let poller = null;
let typingTimer = null;
const TYPING_TIMEOUT = 3000;

// Switch channels
channelList.querySelectorAll('li').forEach(li => {
  li.addEventListener('click', ()=>{
    channelList.querySelectorAll('li').forEach(d=>d.classList.remove('bg-gray-300'));
    li.classList.add('bg-gray-300');
    currentChannelId = parseInt(li.dataset.channelId);
    channelTitle.textContent = li.dataset.channelName;
    chatInput.placeholder = `Message ${li.dataset.channelName}`;
    loadMessages(currentChannelId);
    loadTyping();
  });
});

// Send message
function sendMessage(){
  const txt = chatInput.value.trim();
  if(!txt) return;
  fetch('/employee/chat_api.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'send_message', channel_id: currentChannelId, message:txt })
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      chatInput.value='';
      loadMessages(currentChannelId);
      stopTyping();
    } else {
      alert('Unable to send message: '+data.message);
    }
  })
  .catch(err=>console.error('sendMessage error:', err));
}
chatSend.addEventListener('click', sendMessage);
chatInput.addEventListener('keydown', e=>{
  if(e.key==='Enter') sendMessage();
});

// Typing indicators
chatInput.addEventListener('input', ()=> notifyTyping());
function notifyTyping(){
  clearTimeout(typingTimer);
  typingTimer = setTimeout(stopTyping, TYPING_TIMEOUT);
  fetch('/employee/chat_api.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'typing', channel_id: currentChannelId, typing:true })
  }).catch(err=>console.error('notifyTyping error:',err));
}
function stopTyping(){
  fetch('/employee/chat_api.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'typing', channel_id: currentChannelId, typing:false })
  }).catch(err=>console.error('stopTyping error:',err));
}

// Load messages
function loadMessages(channelId){
  fetch(`/employee/chat_api.php?channel_id=${channelId}`)
    .then(r=>r.json())
    .then(data=>{
      if(!data.success){
        console.error('loadMessages error:', data.message);
        return;
      }
      chatMessages.innerHTML='';
      data.messages.forEach(msg=>{
        const d=document.createElement('div');
        d.className='mb-2';
        let timeStr = new Date(msg.timestamp).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        d.innerHTML=`
          <span class="font-semibold text-[#0C1D36]">${msg.username}:</span> ${msg.text}
          <span class="message-timestamp">${timeStr}</span>
        `;
        chatMessages.appendChild(d);
      });
      chatMessages.scrollTop = chatMessages.scrollHeight;
    })
    .catch(err=>console.error('loadMessages error:',err));
}

// Load typing indicators
function loadTyping(){
  fetch(`/employee/chat_api.php?action=get_typing&channel_id=${currentChannelId}`)
    .then(r=>r.json())
    .then(data=>{
      if(!data.success) return;
      typingIndicators.innerHTML='';
      if(data.typing_users.length>0){
        data.typing_users.forEach(u=>{
          const p=document.createElement('p');
          p.textContent=`${u.username} is typing...`;
          typingIndicators.appendChild(p);
        });
      }
    })
    .catch(err=>console.error('loadTyping error:',err));
}

// Load online users
function loadOnlineUsers(){
  fetch('/employee/chat_api.php?action=online_users')
    .then(r=>r.json())
    .then(data=>{
      if(!data.success) return;
      userList.innerHTML='';
      data.users.forEach(u=>{
        let li=document.createElement('li');
        li.className='px-3 py-1';
        li.textContent=`${u.username} (${u.status})`;
        userList.appendChild(li);
      });
    })
    .catch(err=>console.error('onlineUsers error:',err));
}

// Polling
function startPolling(){
  if(poller) clearInterval(poller);
  poller = setInterval(()=>{
    loadMessages(currentChannelId);
    loadTyping();
    loadOnlineUsers();
  }, 5000);
}

document.addEventListener('DOMContentLoaded', ()=>{
  if(currentChannelId){
    loadMessages(currentChannelId);
    loadTyping();
    loadOnlineUsers();
    startPolling();
  }
});
</script>

</body>
</html>
