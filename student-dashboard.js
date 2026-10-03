// Student Dashboard JavaScript

// Global variables for chat functionality
let currentClassChat = {
    classId: null,
    className: null,
    lastMessageId: 0,
    messagesContainer: null,
    messagePollingInterval: null
};

// Global variables for teacher chat functionality
let currentTeacherChat = {
    teacherId: null,
    classId: null,
    teacherName: null,
    lastMessageId: 0,
    messagesContainer: null,
    messagePollingInterval: null
};

// Global variable to track if notifications have been loaded
let notificationsLoaded = false;

// Class view logging variables
let currentClassViewLogId = null;
let classViewStartTime = null;

// Function to load user data from the server
function loadUserData() {
    // Check if a student_id parameter is in the URL (for parent access)
    const urlParams = new URLSearchParams(window.location.search);
    const studentIdParam = urlParams.get('student_id');
    
    // If student_id parameter exists, we're in parent view mode
    const endpoint = studentIdParam ? 
        `get_child_details.php?student_id=${studentIdParam}` : 
        'get-student-data.php';
    
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Handle either student or child data format
                const studentData = data.student || {};
                updateUserProfile(studentData);
                
                // If this is parent view (from get_child_details.php)
                if (data.classes) {
                    const dashboardData = {
                        student: studentData,
                        classes: data.classes || [],
                        messages: [],  // No messages in parent view
                        summary: {
                            gpa: 'N/A',
                            completed_quizzes: 0,
                            total_quizzes: 0,
                            active_courses: data.classes ? data.classes.length : 0
                        }
                    };
                    updateDashboardContent(dashboardData);
                } else {
                    // Regular student view
                updateDashboardContent(data);
                }
            } else {
                console.error('Error loading user data:', data.message);
                // Redirect to login if not logged in
                if (data.message === 'Not logged in') {
                    window.location.href = 'student-auth.html';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });
}

// Function to update user profile in the dashboard
function updateUserProfile(student) {
    // Update student name
    const studentNameElements = document.querySelectorAll('.student-name');
    studentNameElements.forEach(element => {
        element.textContent = student.full_name;
    });
    
    // Update student ID
    const studentIdElements = document.querySelectorAll('.student-id');
    studentIdElements.forEach(element => {
        element.textContent = student.student_id;
    });
    
    // Check if we're in parent view mode
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('student_id')) {
        showParentViewBanner(student.full_name);
    }
}

// Function to show a banner indicating parent view mode
function showParentViewBanner(studentName) {
    // Check if banner already exists
    if (document.getElementById('parent-view-banner')) return;
    
    const banner = document.createElement('div');
    banner.id = 'parent-view-banner';
    banner.style.backgroundColor = '#f0ad4e';
    banner.style.color = 'white';
    banner.style.padding = '10px';
    banner.style.textAlign = 'center';
    banner.style.fontWeight = 'bold';
    banner.style.position = 'fixed';
    banner.style.top = '70px';
    banner.style.left = '0';
    banner.style.width = '100%';
    banner.style.zIndex = '999';
    banner.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
    
    const backButton = document.createElement('a');
    backButton.href = 'parent-dashboard.html';
    backButton.textContent = '← Back to Parent Dashboard';
    backButton.style.color = 'white';
    backButton.style.marginRight = '20px';
    backButton.style.textDecoration = 'none';
    
    banner.appendChild(backButton);
    banner.appendChild(document.createTextNode(`You are viewing ${studentName}'s dashboard as a parent`));
    
    document.body.insertBefore(banner, document.body.firstChild);
    
    // Adjust main content margin to make room for banner
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.marginTop = '110px';
    }
}

// Function to update dashboard content
function updateDashboardContent(data) {
    // Update student name and ID
    updateUserProfile(data.student);
    
    // Update classes
    updateClasses(data.classes);
    
    // Update messages
    updateMessages(data.messages);
    
    // Update summary statistics
    updateSummaryStatistics(data.summary);
}

// Function to update classes
function updateClasses(classes) {
    const classesList = document.querySelector('.classes-list');
    if (!classesList) return;
    
    classesList.innerHTML = classes.map(cls => `
        <div class="class-item" data-class-id="${cls.id}">
            <button class="leave-class-btn" onclick="confirmLeaveClass(${cls.id}, '${cls.class_name.replace(/'/g, "\\'")}')">
                <i class="fas fa-times"></i>
            </button>
            <div class="class-info">
                <h3>${cls.class_name}</h3>
                <p><i class="fas fa-user-tie"></i> Teacher: ${cls.teacher_name}</p>
                <p class="class-time">
                    <i class="fas fa-clock"></i> 
                    Joined: ${new Date(cls.created_at).toLocaleDateString()}
                </p>
                <div class="class-actions">
                    <button class="btn btn-primary" onclick="viewClass(${cls.id}, '${cls.class_name.replace(/'/g, "\\'")}', '${cls.teacher_name.replace(/'/g, "\\'")}')">
                        <i class="fas fa-book"></i> View Class
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Function to update messages
function updateMessages(messages) {
    const messagesList = document.querySelector('.messages-list');
    if (!messagesList) return;
    
    messagesList.innerHTML = messages.map(msg => `
        <div class="message-item ${msg.unread ? 'unread' : ''}">
            <div class="message-info">
                <h3>${msg.sender}</h3>
                <p>${msg.subject}</p>
                <span class="message-time">${msg.time}</span>
            </div>
            ${msg.unread ? '<span class="unread-badge"></span>' : ''}
        </div>
    `).join('');
}

// Function to update summary statistics
function updateSummaryStatistics(summary) {
    // Update statistics in the dashboard
    const statsElements = document.querySelectorAll('.stat-value');
    if (statsElements.length >= 3) {
        statsElements[0].textContent = summary.gpa;
        statsElements[1].textContent = `${summary.completed_quizzes}/${summary.total_quizzes}`;
        statsElements[2].textContent = summary.active_courses;
    }
}

// Function to handle navigation between sections
function navigateToSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.style.display = 'none';
    });
    
    // Show the selected section
    const selectedSection = document.getElementById(`${sectionId}-section`);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }
    
    // Update active state of action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.classList.remove('active');
        if (card.getAttribute('onclick').includes(sectionId)) {
            card.classList.add('active');
        }
    });
}

// Function to handle logout
function handleLogout() {
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'student-auth.html';
            } else {
                console.error('Logout failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
        });
}

// Function to handle lesson filters
function setupLessonFilters() {
    const subjectFilter = document.getElementById('subjectFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (subjectFilter && statusFilter) {
        subjectFilter.addEventListener('change', filterLessons);
        statusFilter.addEventListener('change', filterLessons);
    }
}

// Function to filter lessons based on selected criteria
function filterLessons() {
    const subjectFilter = document.getElementById('subjectFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const lessonCards = document.querySelectorAll('.lesson-card');
    
    lessonCards.forEach(card => {
        const subject = card.querySelector('.lesson-subject').classList[1];
        const status = card.querySelector('.lesson-status').classList[1];
        
        const subjectMatch = subjectFilter === 'all' || subject === subjectFilter;
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        
        card.style.display = subjectMatch && statusMatch ? 'block' : 'none';
    });
}

// Function to handle lesson card interactions
function setupLessonCards() {
    const lessonCards = document.querySelectorAll('.lesson-card');
    
    lessonCards.forEach(card => {
        const viewButton = card.querySelector('.view-lesson');
        if (viewButton) {
            viewButton.addEventListener('click', function() {
                const lessonTitle = card.querySelector('.lesson-title').textContent;
                const lessonStatus = card.querySelector('.lesson-status').classList[1];
                
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                this.disabled = true;
                
                // Simulate loading the lesson
                setTimeout(() => {
                    alert(`Opening lesson: ${lessonTitle}\nStatus: ${lessonStatus}\nThis would redirect to the lesson page in a real application.`);
                    
                    // Reset button state
                    this.innerHTML = lessonStatus === 'completed' ? 'View Lesson' : 
                                   lessonStatus === 'in-progress' ? 'Continue Lesson' : 'Start Lesson';
                    this.disabled = false;
                }, 1500);
            });
        }
    });
}

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadUserData();
    loadClasses();
    loadMessages();

    // Set up navigation
    setupNavigation();

    // Set up lesson functionality
    setupLessonFilters();
    setupLessonCards();
    
    // Load initial data
    loadDashboardData(document.getElementById('academicYearFilter').value);
    
    // Handle assessment button clicks
    const assessmentButtons = document.querySelectorAll('.start-assessment');
    
    assessmentButtons.forEach(button => {
        button.addEventListener('click', function() {
            const assessmentItem = this.closest('.assessment-item');
            const assessmentTitle = assessmentItem.querySelector('h3').textContent;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.disabled = true;
            
            // Simulate loading the assessment
            setTimeout(() => {
                // In a real application, this would redirect to the assessment page
                alert(`Starting assessment: ${assessmentTitle}\nThis would redirect to the assessment page in a real application.`);
                
                // Reset button state
                this.innerHTML = 'Start Assessment';
                this.disabled = false;
            }, 1500);
        });
    });

    // Update progress bars with animation
    const progressBars = document.querySelectorAll('.progress');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });

    // Add click handlers for message items
    setupMessageHandlers();
    
    // Add click handlers for class items
    setupClassHandlers();
});

// Function to load classes
function loadClasses() {
    console.log('Loading classes...');
    
    fetch('get_student_classes.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Classes data:', data); // Debug log
            
            if (data.success) {
                const classesList = document.querySelector('.classes-list');
                if (classesList) {
                    if (!data.classes || data.classes.length === 0) {
                        classesList.innerHTML = `
                            <div class="no-classes">
                                <p>You haven't joined any classes yet.</p>
                                <p>Click the "Join Class" button to get started!</p>
                            </div>
                        `;
                    } else {
                        classesList.innerHTML = data.classes.map(cls => `
                            <div class="class-item" data-class-id="${cls.id}">
                                <button class="leave-class-btn" onclick="confirmLeaveClass(${cls.id}, '${cls.class_name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="class-info">
                                    <h3>${cls.class_name}</h3>
                                    <p><i class="fas fa-user-tie"></i> Teacher: ${cls.teacher_name}</p>
                                    <p class="class-time">
                                        <i class="fas fa-clock"></i> 
                                        Joined: ${new Date(cls.created_at).toLocaleDateString()}
                                    </p>
                                    <div class="class-actions">
                                        <button class="btn btn-primary" onclick="viewClass(${cls.id}, '${cls.class_name.replace(/'/g, "\\'")}', '${cls.teacher_name.replace(/'/g, "\\'")}')">
                                            <i class="fas fa-book"></i> View Class
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                } else {
                    console.error('Classes list element not found');
                }
            } else {
                console.error('Failed to load classes:', data.message);
                showNotification(data.message || 'Failed to load classes', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            showNotification('An error occurred while loading classes', 'error');
        });
}

// Function to view a specific class in a modal popup
function viewClass(classId, className, teacherName) {
    // Prevent the event from bubbling up
    event.preventDefault();
    event.stopPropagation();
    
    console.log(`Opening class: ${className} (ID: ${classId}) taught by ${teacherName}`);
    
    // Sanitize inputs to prevent XSS
    className = className || 'Class Details';
    teacherName = teacherName || 'Unknown Teacher';
    
    // Log the class view
    logClassView(classId);
    
    // Create the class details modal dynamically if it doesn't exist
    let classDetailsModal = document.getElementById('classDetailsModal');
    
    if (!classDetailsModal) {
        // Create modal element
        classDetailsModal = document.createElement('div');
        classDetailsModal.id = 'classDetailsModal';
        classDetailsModal.className = 'modal';
        
        // Add it to the body
        document.body.appendChild(classDetailsModal);
    }
    
    // Populate modal content
    classDetailsModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-family: 'Merriweather', serif; font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding-left: 10px; color: white;">${className}</h2>
                <span class="close-modal" onclick="closeClassDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="class-details">
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-user"></i> Teacher:</span>
                        <span class="detail-value">${teacherName}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-id-card"></i> Class ID:</span>
                        <span class="detail-value">${classId}</span>
                    </div>
                    
                    <div class="chat-buttons">
                        <button class="chat-btn" onclick="viewAnnouncements('${classId}', '${className}')">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </button>
                        <button class="chat-btn" onclick="openClassChat('${classId}', '${className}')">
                            <i class="fas fa-comments"></i> Class Chat
                        </button>
                        <button class="chat-btn" onclick="openTeacherChat('${classId}', '${teacherName}')">
                            <i class="fas fa-user-tie"></i> Teacher Chat
                        </button>
                    </div>
                    
                    <div class="class-content">
                        <h3><i class="fas fa-book"></i> Available Lessons</h3>
                        <div class="class-lessons" id="class-lessons-list">
                            <p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading lessons...</p>
                        </div>
                        
                        <h3><i class="fas fa-tasks"></i> Assessments</h3>
                        <div class="class-assessments" id="class-assessments-list">
                            <p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading assessments...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Popup Templates -->
        <div id="classChatWindow" class="chat-popup">
            <div class="chat-header">
                <h3><i class="fas fa-comments"></i> <span id="classChatTitle"></span></h3>
                <button class="close-chat" onclick="closeClassChat()"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-messages" id="classChatMessages">
                <!-- Messages will be loaded dynamically -->
                <div class="message received">
                    <div class="message-content">Welcome to the class chat!</div>
                    <div class="message-info">System, just now</div>
                </div>
            </div>
            <div class="chat-input-container">
                <input type="text" class="chat-input" id="classChatInput" placeholder="Type your message here..." onkeypress="if(event.key === 'Enter') sendClassMessage()">
                <button class="send-message-btn" onclick="sendClassMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        
        <div id="teacherChatWindow" class="chat-popup">
            <div class="chat-header">
                <h3><i class="fas fa-user-tie"></i> <span id="teacherChatTitle"></span></h3>
                <button class="close-chat" onclick="closeTeacherChat()"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-messages" id="teacherChatMessages">
                <!-- Messages will be loaded dynamically -->
                <div class="message received">
                    <div class="message-content">Hello! How can I help you today?</div>
                    <div class="message-info">Teacher, just now</div>
                </div>
            </div>
            <div class="chat-input-container">
                <input type="text" class="chat-input" id="teacherChatInput" placeholder="Type your message here..." onkeypress="if(event.key === 'Enter') sendTeacherMessage()">
                <button class="send-message-btn" onclick="sendTeacherMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        
        <div id="chatOverlay" class="overlay" onclick="closeAllChats()"></div>
    `;
    
    // Ensure modal has highest z-index and is visible
    classDetailsModal.style.display = 'block';
    classDetailsModal.style.zIndex = '99999';
    
    // Bring modal to front
    document.body.appendChild(classDetailsModal);
    
    // Load class details
    loadClassDetails(classId);
}

// Function to open class chat
function openClassChat(classId, className) {
    // Close the class details modal first to prevent overlap
    closeClassDetailsModal();
    
    // Clear any existing polling interval
    if (currentClassChat.messagePollingInterval) {
        clearInterval(currentClassChat.messagePollingInterval);
    }
    
    // Set current chat details
    currentClassChat.classId = classId;
    currentClassChat.className = className;
    currentClassChat.messagesContainer = document.getElementById('classChatMessages');
    
    // Update chat title
    document.getElementById('classChatTitle').textContent = `${className} Chat`;
    
    // Show chat window and overlay
    document.getElementById('classChatWindow').classList.add('active');
    document.getElementById('chatOverlay').classList.add('active');
    
    // Reset messages container and show loading state
    currentClassChat.messagesContainer.innerHTML = `
        <div class="chat-loading">
            <i class="fas fa-spinner fa-spin"></i> Loading messages...
        </div>
    `;
    
    // Load messages
    loadClassChatMessages(classId);
    
    // Start polling for new messages every 5 seconds
    currentClassChat.messagePollingInterval = setInterval(() => {
        loadClassChatMessages(classId, true);
    }, 5000);
    
    // Focus on input
    setTimeout(() => {
        document.getElementById('classChatInput').focus();
    }, 300);
}

// Function to close class chat
function closeClassChat() {
    // Hide the chat window and overlay
    const classChatWindow = document.getElementById('classChatWindow');
    if (classChatWindow) {
        classChatWindow.classList.remove('active');
    }
    
    // Only remove overlay if teacher chat is not active
    if (!document.getElementById('teacherChatWindow').classList.contains('active')) {
    document.getElementById('chatOverlay').classList.remove('active');
    }
    
    // Clear polling interval if exists
    if (window.classChatInterval) {
        clearInterval(window.classChatInterval);
        window.classChatInterval = null;
    }
}

// Function to load class chat messages
function loadClassChatMessages(classId, isPolling = false) {
    // If polling and no chat is active, don't proceed
    if (isPolling && !currentClassChat.classId) {
        return;
    }
    
    fetch(`get_class_chat_messages.php?class_id=${classId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // If not polling or empty container, replace all content
                if (!isPolling || currentClassChat.messagesContainer.children.length <= 1) {
                    displayClassChatMessages(data.messages);
                } else {
                    // If polling, only add new messages
                    updateClassChatWithNewMessages(data.messages);
                }
            } else {
                // Show error message
                currentClassChat.messagesContainer.innerHTML = `
                    <div class="chat-error">
                        <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to load messages'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading chat messages:', error);
            
            // Only show error if not polling to avoid disrupting chat
            if (!isPolling) {
                currentClassChat.messagesContainer.innerHTML = `
                    <div class="chat-error">
                        <i class="fas fa-exclamation-circle"></i> Error loading messages. Please try again.
            </div>
        `;
            }
        });
}

// Function to display chat messages
function displayClassChatMessages(messages) {
    // If no messages, show empty state
    if (!messages || messages.length === 0) {
        currentClassChat.messagesContainer.innerHTML = `
            <div class="chat-empty-state">
                <i class="fas fa-comments"></i>
                <p>No messages yet. Be the first to say hello!</p>
                </div>
            `;
        return;
    }
    
    // Group messages by date
    const messagesByDate = {};
    messages.forEach(msg => {
        if (!messagesByDate[msg.formatted_date]) {
            messagesByDate[msg.formatted_date] = [];
        }
        messagesByDate[msg.formatted_date].push(msg);
        
        // Track the highest message ID
        if (msg.id > currentClassChat.lastMessageId) {
            currentClassChat.lastMessageId = parseInt(msg.id);
        }
    });
    
    // Build HTML for all messages
    let html = '';
    
    Object.keys(messagesByDate).forEach(date => {
        // Add date separator
        html += `<div class="date-separator"><span>${date}</span></div>`;
        
        // Add messages for this date
        messagesByDate[date].forEach(msg => {
            html += `
                <div class="message ${msg.is_own ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-content">${escapeHtml(msg.message)}</div>
                    <div class="message-info">${msg.is_own ? 'You' : escapeHtml(msg.sender_name)}, ${msg.formatted_time}</div>
            </div>
        `;
        });
    });
        
    currentClassChat.messagesContainer.innerHTML = html;
        
        // Scroll to bottom
    scrollChatToBottom();
}

// Function to update chat with only new messages
function updateClassChatWithNewMessages(messages) {
    if (!messages || messages.length === 0) {
        return;
    }
    
    // Get only messages newer than our last seen message
    const newMessages = messages.filter(msg => parseInt(msg.id) > currentClassChat.lastMessageId);
    
    if (newMessages.length === 0) {
        return;
    }
    
    // Group new messages by date
    const messagesByDate = {};
    newMessages.forEach(msg => {
        if (!messagesByDate[msg.formatted_date]) {
            messagesByDate[msg.formatted_date] = [];
        }
        messagesByDate[msg.formatted_date].push(msg);
        
        // Update last seen message ID
        if (msg.id > currentClassChat.lastMessageId) {
            currentClassChat.lastMessageId = parseInt(msg.id);
        }
    });
    
    // Check if we need to add date separators
    const existingDates = Array.from(currentClassChat.messagesContainer.querySelectorAll('.date-separator'))
        .map(el => el.textContent.trim());
    
    // Add new messages
    Object.keys(messagesByDate).forEach(date => {
        // If this date doesn't exist yet, add a separator
        if (!existingDates.includes(date)) {
            const dateSeparator = document.createElement('div');
            dateSeparator.className = 'date-separator';
            dateSeparator.innerHTML = `<span>${date}</span>`;
            currentClassChat.messagesContainer.appendChild(dateSeparator);
        }
        
        // Add all messages for this date
        messagesByDate[date].forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', msg.id);
            
            messageDiv.innerHTML = `
                <div class="message-content">${escapeHtml(msg.message)}</div>
                <div class="message-info">${msg.is_own ? 'You' : escapeHtml(msg.sender_name)}, ${msg.formatted_time}</div>
            `;
            
            currentClassChat.messagesContainer.appendChild(messageDiv);
        });
    });
    
    // If new messages were added, scroll to bottom
    if (newMessages.length > 0) {
        scrollChatToBottom();
        
        // Add a visual indication for new messages
        newMessages.forEach(msg => {
            const element = currentClassChat.messagesContainer.querySelector(`[data-message-id="${msg.id}"]`);
            if (element) {
                element.classList.add('new-message');
                setTimeout(() => {
                    element.classList.remove('new-message');
                }, 1000);
            }
        });
    }
}

// Function to send message in class chat
function sendClassMessage() {
    const input = document.getElementById('classChatInput');
    const message = input.value.trim();
    
    // Don't send empty messages
    if (!message) {
        return;
    }
    
    // Disable input while sending
    input.disabled = true;
    
    // Send message to server
    fetch('send_class_chat_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: currentClassChat.classId,
            message: message
        })
    })
        .then(response => {
            if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
            // Clear input
            input.value = '';
            
            // Add the new message to the chat
            const newMessage = data.data;
            
            // Check if we need to add a date separator
            const today = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const existingDates = Array.from(currentClassChat.messagesContainer.querySelectorAll('.date-separator'))
                .map(el => el.textContent.trim());
            
            if (!existingDates.includes(today)) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'date-separator';
                dateSeparator.innerHTML = `<span>${today}</span>`;
                currentClassChat.messagesContainer.appendChild(dateSeparator);
            }
            
            // Add the message
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.setAttribute('data-message-id', newMessage.id);
            
            messageDiv.innerHTML = `
                <div class="message-content">${escapeHtml(newMessage.message)}</div>
                <div class="message-info">You, ${newMessage.formatted_time}</div>
            `;
            
            currentClassChat.messagesContainer.appendChild(messageDiv);
            
            // Update last message ID
            if (newMessage.id > currentClassChat.lastMessageId) {
                currentClassChat.lastMessageId = parseInt(newMessage.id);
            }
            
            // Add animation class
            messageDiv.classList.add('new-message');
            setTimeout(() => {
                messageDiv.classList.remove('new-message');
            }, 1000);
            
            // Scroll to bottom
            scrollChatToBottom();
                                } else {
            // Show error
            console.error('Error sending message:', data.message);
            alert('Failed to send message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    })
    .finally(() => {
        // Re-enable input
        input.disabled = false;
        input.focus();
    });
}

// Helper function to scroll chat to bottom
function scrollChatToBottom() {
    if (currentClassChat.messagesContainer) {
        currentClassChat.messagesContainer.scrollTop = currentClassChat.messagesContainer.scrollHeight;
    }
}

// Helper function to escape HTML to prevent XSS
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Function to open teacher chat
function openTeacherChat(classId, teacherName) {
    // Close the class details modal first to prevent overlap
    closeClassDetailsModal();
    
    // Get teacher ID from the class
    getTeacherIdForClass(classId)
        .then(teacherId => {
            if (!teacherId) {
                console.error('Could not determine teacher ID for class:', classId);
                showNotification('Could not connect to teacher chat. Please try again later.', 'error');
                return;
            }
            
            // Clear any existing polling interval
            if (currentTeacherChat.messagePollingInterval) {
                clearInterval(currentTeacherChat.messagePollingInterval);
            }
            
            // Set current chat details
            currentTeacherChat.teacherId = teacherId;
            currentTeacherChat.classId = classId;
            currentTeacherChat.teacherName = teacherName;
            currentTeacherChat.messagesContainer = document.getElementById('teacherChatMessages');
            
            // Update chat title
            document.getElementById('teacherChatTitle').textContent = `Chat with ${teacherName}`;
            
            // Show chat window and overlay
            document.getElementById('teacherChatWindow').classList.add('active');
            document.getElementById('chatOverlay').classList.add('active');
            
            // Reset messages container and show loading state
            currentTeacherChat.messagesContainer.innerHTML = `
                <div class="chat-loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading messages...
        </div>
    `;
    
            // Load messages
            loadTeacherStudentMessages(teacherId, classId);
            
            // Start polling for new messages every 5 seconds
            currentTeacherChat.messagePollingInterval = setInterval(() => {
                loadTeacherStudentMessages(teacherId, classId, true);
            }, 5000);
            
            // Focus on input
            setTimeout(() => {
                document.getElementById('teacherChatInput').focus();
            }, 300);
        })
        .catch(error => {
            console.error('Error getting teacher ID:', error);
            showNotification('Could not connect to teacher chat. Please try again later.', 'error');
        });
}

// Helper function to get teacher ID for a class
function getTeacherIdForClass(classId) {
    return new Promise((resolve, reject) => {
        // Always use the API to get the teacher ID to ensure correctness
        fetch(`get_class_teacher.php?class_id=${classId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
                if (data.success && data.teacher_id) {
                    console.log(`Successfully got teacher ID for class ${classId}: ${data.teacher_id}`);
                    resolve(data.teacher_id);
                } else {
                    console.error('Could not get teacher ID:', data.message);
                    reject(new Error(data.message || 'Could not get teacher ID'));
                }
            })
            .catch(error => {
                console.error('Error fetching teacher ID:', error);
                reject(error);
            });
    });
}

// Function to close the teacher chat window
function closeTeacherChat() {
    // Hide the chat window and overlay
    document.getElementById('teacherChatWindow').classList.remove('active');
    document.getElementById('chatOverlay').classList.remove('active');
    
    // Clear polling interval
    if (currentTeacherChat.messagePollingInterval) {
        clearInterval(currentTeacherChat.messagePollingInterval);
        currentTeacherChat.messagePollingInterval = null;
    }
}

// Function to close all chat windows
function closeAllChats() {
    closeTeacherChat();
    
    // If there's a class chat open, close it too
    const classChatWindow = document.getElementById('classChatWindow');
    if (classChatWindow && classChatWindow.classList.contains('active')) {
        closeClassChat();
    }
    
    // If announcements window is open, close it too
    const announcementsWindow = document.getElementById('announcementsWindow');
    if (announcementsWindow && announcementsWindow.classList.contains('active')) {
        closeAnnouncementsWindow();
    }
}

// Function to load teacher-student messages
function loadTeacherStudentMessages(teacherId, classId, isPolling = false) {
    // If polling and no chat is active, don't proceed
    if (isPolling && !currentTeacherChat.teacherId) {
        return;
    }
    
    fetch(`get_teacher_student_messages.php?teacher_id=${teacherId}&class_id=${classId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // If not polling or empty container, replace all content
                if (!isPolling || currentTeacherChat.messagesContainer.children.length <= 1) {
                    displayTeacherStudentMessages(data.messages);
            } else {
                    // If polling, only add new messages
                    updateTeacherChatWithNewMessages(data.messages);
                }
            } else {
                // Show error message
                currentTeacherChat.messagesContainer.innerHTML = `
                    <div class="chat-error">
                        <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to load messages'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading teacher chat messages:', error);
            
            // Only show error if not polling to avoid disrupting chat
            if (!isPolling) {
                currentTeacherChat.messagesContainer.innerHTML = `
                    <div class="chat-error">
                        <i class="fas fa-exclamation-circle"></i> Error loading messages. Please try again.
                </div>
            `;
            }
        });
}

// Function to display teacher-student messages
function displayTeacherStudentMessages(messages) {
    // If no messages, show empty state
    if (!messages || messages.length === 0) {
        currentTeacherChat.messagesContainer.innerHTML = `
            <div class="chat-empty-state">
                <i class="fas fa-comments"></i>
                <p>No messages yet. Send a message to start the conversation with ${currentTeacherChat.teacherName}!</p>
            </div>
        `;
        return;
    }
    
    // Group messages by date
    const messagesByDate = {};
    messages.forEach(msg => {
        if (!messagesByDate[msg.formatted_date]) {
            messagesByDate[msg.formatted_date] = [];
        }
        messagesByDate[msg.formatted_date].push(msg);
        
        // Track the highest message ID
        if (msg.id > currentTeacherChat.lastMessageId) {
            currentTeacherChat.lastMessageId = parseInt(msg.id);
        }
    });
    
    // Build HTML for all messages
    let html = '';
    
    Object.keys(messagesByDate).forEach(date => {
        // Add date separator
        html += `<div class="date-separator"><span>${date}</span></div>`;
        
        // Add messages for this date
        messagesByDate[date].forEach(msg => {
            // Add sender type badge for teacher messages
            const senderTypeHtml = msg.is_own ? '' : 
                `<span class="sender-type teacher">Teacher</span>`;
                
            html += `
                <div class="message ${msg.is_own ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-content">${escapeHtml(msg.message)}</div>
                <div class="message-info">
                        ${senderTypeHtml}${msg.is_own ? 'You' : escapeHtml(msg.sender_name)}, ${msg.formatted_time}
                </div>
            </div>
            `;
        });
    });
    
    currentTeacherChat.messagesContainer.innerHTML = html;
    
    // Scroll to bottom
    scrollTeacherChatToBottom();
}

// Function to update teacher chat with only new messages
function updateTeacherChatWithNewMessages(messages) {
    if (!messages || messages.length === 0) {
        return;
    }
    
    // Get only messages newer than our last seen message
    const newMessages = messages.filter(msg => parseInt(msg.id) > currentTeacherChat.lastMessageId);
    
    if (newMessages.length === 0) {
        return;
    }
    
    // Group new messages by date
    const messagesByDate = {};
    newMessages.forEach(msg => {
        if (!messagesByDate[msg.formatted_date]) {
            messagesByDate[msg.formatted_date] = [];
        }
        messagesByDate[msg.formatted_date].push(msg);
        
        // Update last seen message ID
        if (msg.id > currentTeacherChat.lastMessageId) {
            currentTeacherChat.lastMessageId = parseInt(msg.id);
        }
    });
    
    // Check if we need to add date separators
    const existingDates = Array.from(currentTeacherChat.messagesContainer.querySelectorAll('.date-separator'))
        .map(el => el.textContent.trim());
    
    // Add new messages
    Object.keys(messagesByDate).forEach(date => {
        // If this date doesn't exist yet, add a separator
        if (!existingDates.includes(date)) {
            const dateSeparator = document.createElement('div');
            dateSeparator.className = 'date-separator';
            dateSeparator.innerHTML = `<span>${date}</span>`;
            currentTeacherChat.messagesContainer.appendChild(dateSeparator);
        }
        
        // Add all messages for this date
        messagesByDate[date].forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', msg.id);
            
            // Add sender type badge for teacher messages
            const senderTypeHtml = msg.is_own ? '' : 
                `<span class="sender-type teacher">Teacher</span>`;
                
            messageDiv.innerHTML = `
                <div class="message-content">${escapeHtml(msg.message)}</div>
                <div class="message-info">
                    ${senderTypeHtml}${msg.is_own ? 'You' : escapeHtml(msg.sender_name)}, ${msg.formatted_time}
                </div>
            `;
            
            currentTeacherChat.messagesContainer.appendChild(messageDiv);
        });
    });
    
    // If new messages were added, scroll to bottom
    if (newMessages.length > 0) {
        scrollTeacherChatToBottom();
        
        // Add a visual indication for new messages
        newMessages.forEach(msg => {
            const element = currentTeacherChat.messagesContainer.querySelector(`[data-message-id="${msg.id}"]`);
            if (element) {
                element.classList.add('new-message');
                setTimeout(() => {
                    element.classList.remove('new-message');
                }, 1000);
            }
        });
        
        // Play notification sound for teacher messages
        const teacherMessages = newMessages.filter(msg => !msg.is_own);
        if (teacherMessages.length > 0) {
            playMessageNotificationSound();
        }
    }
}

// Function to send message in teacher chat
function sendTeacherMessage() {
    const input = document.getElementById('teacherChatInput');
    const message = input.value.trim();
    
    // Don't send empty messages
    if (!message) {
        return;
    }
    
    // Disable input while sending
    input.disabled = true;
    
    // Send message to server
    fetch('send_teacher_student_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            teacher_id: currentTeacherChat.teacherId,
            class_id: currentTeacherChat.classId,
            message: message
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            
            // Add the new message to the chat
            const newMessage = data.data;
            
            // Check if we need to add a date separator
            const today = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const existingDates = Array.from(currentTeacherChat.messagesContainer.querySelectorAll('.date-separator'))
                .map(el => el.textContent.trim());
            
            if (!existingDates.includes(today)) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'date-separator';
                dateSeparator.innerHTML = `<span>${today}</span>`;
                currentTeacherChat.messagesContainer.appendChild(dateSeparator);
            }
            
            // Add the message
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.setAttribute('data-message-id', newMessage.id);
            
            messageDiv.innerHTML = `
                <div class="message-content">${escapeHtml(newMessage.message)}</div>
                <div class="message-info">You, ${newMessage.formatted_time}</div>
            `;
            
            currentTeacherChat.messagesContainer.appendChild(messageDiv);
            
            // Update last message ID
            if (newMessage.id > currentTeacherChat.lastMessageId) {
                currentTeacherChat.lastMessageId = parseInt(newMessage.id);
            }
            
            // Add animation class
            messageDiv.classList.add('new-message');
            setTimeout(() => {
                messageDiv.classList.remove('new-message');
            }, 1000);
            
            // Scroll to bottom
            scrollTeacherChatToBottom();
        } else {
            // Show error
            console.error('Error sending message:', data.message);
            alert('Failed to send message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    })
    .finally(() => {
        // Re-enable input
        input.disabled = false;
        input.focus();
    });
}

// Helper function to scroll teacher chat to bottom
function scrollTeacherChatToBottom() {
    if (currentTeacherChat.messagesContainer) {
        currentTeacherChat.messagesContainer.scrollTop = currentTeacherChat.messagesContainer.scrollHeight;
    }
}

// Function to play notification sound for new messages
function playMessageNotificationSound() {
    // Create a simple beep sound using Web Audio API
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime); // A5
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.start();
        gainNode.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + 0.5);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (e) {
        console.log('Web Audio API not supported or blocked by browser.');
    }
}

// Function to view assessment details
function viewAssessmentDetails(assessmentId) {
    console.log('Viewing assessment details for assessment ID:', assessmentId);
    
    // Create the assessment details modal dynamically if it doesn't exist
    let assessmentDetailsModal = document.getElementById('assessmentDetailsModal');
    
    if (!assessmentDetailsModal) {
        // Create modal element
        assessmentDetailsModal = document.createElement('div');
        assessmentDetailsModal.id = 'assessmentDetailsModal';
        assessmentDetailsModal.className = 'modal';
        
        // Add it to the body
        document.body.appendChild(assessmentDetailsModal);
        console.log('Created new assessment details modal');
    }
    
    // Show loading state
    assessmentDetailsModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-family: 'Merriweather', serif; font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding-left: 10px; color: white;">Assessment</h2>
                <span class="close-modal" onclick="closeAssessmentDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading assessment details...</p>
            </div>
        </div>
    `;
    
    // Ensure modal has highest z-index and is visible
    assessmentDetailsModal.style.display = 'block';
    assessmentDetailsModal.style.zIndex = '99999';
    
    // Bring modal to front
    document.body.appendChild(assessmentDetailsModal);
    
    console.log('Showing assessment details modal');
    
    // Fetch assessment details
    fetch(`get_student_assessment_details.php?id=${assessmentId}`)
        .then(response => {
            console.log('Assessment details API response status:', response.status);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Assessment details data:', data);
            
            if (data.success) {
                const assessment = data.data;
                console.log('Assessment data:', assessment);
                
                // Check if student already has a submission
                const hasSubmission = assessment.submission && assessment.submission.id;
                
                // Update modal content
                assessmentDetailsModal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 style="font-family: 'Merriweather', serif; font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding-left: 10px; color: white;">${assessment.title || 'Assessment'}</h2>
                            <span class="close-modal" onclick="closeAssessmentDetailsModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="assessment-details">
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-book"></i> Class:</span>
                                    <span class="detail-value">${assessment.class_name || 'Unknown Class'}</span>
                                </div>
                                ${assessment.due_date ? `
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-clock"></i> Due Date:</span>
                                    <span class="detail-value due-date-value">${new Date(assessment.due_date).toLocaleDateString()}</span>
                                </div>` : ''}
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-calendar"></i> Added:</span>
                                    <span class="detail-value">${assessment.created_at ? new Date(assessment.created_at).toLocaleDateString() : 'Unknown date'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-star"></i> Total Score:</span>
                                    <span class="detail-value total-score">${assessment.max_score} points</span>
                                </div>
                                
                                <div class="assessment-content">
                                    <h3><i class="fas fa-info-circle"></i> Description</h3>
                                    <div class="assessment-description">
                                        <p>${assessment.description || 'No description provided.'}</p>
                                    </div>
                                    
                                    <h3><i class="fas fa-tasks"></i> Instructions</h3>
                                    <div class="assessment-instructions">
                                        <p>${assessment.instructions || 'No instructions provided.'}</p>
                                    </div>
                                    
                                    ${assessment.links && assessment.links.length > 0 ? `
                                    <h3><i class="fas fa-link"></i> External Links</h3>
                                    <div class="assessment-links">
                                        ${assessment.links.map(link => {
                                            // Get domain name for icon display
                                            let domain = '';
                                            try {
                                                const url = new URL(link.link_url);
                                                domain = url.hostname.replace('www.', '');
                                            } catch(e) {
                                                domain = 'external-link';
                                            }
                                            
                                            return `
                                                <div class="link-item">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    <div class="link-info">
                                                        <span class="link-title">${link.link_title}</span>
                                                        <a href="${link.link_url}" class="link-url" target="_blank">
                                                            ${domain ? domain : link.link_url}
                                                        </a>
                                                    </div>
                                                    <a href="${link.link_url}" class="link-open-btn" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i> Open
                                                    </a>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                    ` : ''}
                                    
                                    <h3><i class="fas fa-file"></i> Resources</h3>
                                    <div class="assessment-files">
                                        ${assessment.files && assessment.files.length > 0 ? 
                                            assessment.files.map(file => `
                                                <div class="file-item">
                                                    <i class="fas fa-file-alt"></i>
                                                    <span class="file-name">${file.file_name}</span>
                                                    <a href="${file.file_path}" class="file-download">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                            `).join('') : 
                                            '<p>No files attached to this assessment.</p>'
                                        }
                                    </div>
                                    
                                    ${hasSubmission ? `
                                        <div class="submission-details">
                                            <h3><i class="fas fa-check-circle"></i> Your Submission</h3>
                                            <div class="submission-info">
                                                <p><strong>Submitted:</strong> ${new Date(assessment.submission.submission_date).toLocaleString()}</p>
                                                ${assessment.submission.score !== null ? `<p><strong>Grade:</strong> ${assessment.submission.score}</p>` : ''}
                                                ${assessment.submission.teacher_feedback ? `<p><strong>Feedback:</strong> ${assessment.submission.teacher_feedback}</p>` : ''}
                                                
                                                ${assessment.submission.files && assessment.submission.files.length > 0 ? `
                                                    <h4>Submitted Files:</h4>
                                                    <div class="submitted-files">
                                                        ${assessment.submission.files.map(file => `
                                                            <div class="file-item">
                                                                <i class="fas fa-file-alt"></i>
                                                                <span class="file-name">${file.file_name}</span>
                                                                <a href="${file.file_path}" class="file-download">
                                                                    <i class="fas fa-download"></i> Download
                                                                </a>
                                                            </div>
                                                        `).join('')}
                                                    </div>
                                                ` : '<p>No files were submitted.</p>'}
                                                
                                                ${assessment.submission.score === null ? `
                                                <div class="submission-actions" style="margin-top: 30px; text-align: center;">
                                                        <button class="btn btn-danger unsubmit-btn" onclick="unsubmitAssessment(${assessment.id})" data-graded="false">
                                                        <i class="fas fa-trash-alt"></i> Unsubmit Assignment
                                                    </button>
                                                </div>
                                                ` : `
                                                    <div class="submission-actions" style="margin-top: 30px; text-align: center;">
                                                        <button class="btn btn-secondary" disabled data-graded="true">
                                                            <i class="fas fa-lock"></i> Assignment Graded - Cannot Unsubmit
                                                        </button>
                                                    </div>
                                                `}
                                            </div>
                                        </div>
                                    ` : `
                                        <div class="assessment-actions">
                                            <form id="submission-form" class="submission-form" enctype="multipart/form-data">
                                                <input type="hidden" name="assessment_id" value="${assessment.id}">
                                                <div class="file-upload-container">
                                                    <label for="file-upload" class="file-upload-label">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <span>Drag your file here or click to browse</span>
                                                    </label>
                                                    <input type="file" id="file-upload" name="submission_file" class="file-upload-input" required>
                                                    <div class="selected-file-name">No file selected</div>
                                                </div>
                                                <div class="submission-actions">
                                                    <button type="submit" class="btn btn-primary submit-assessment-btn">
                                                        <i class="fas fa-paper-plane"></i> Submit Assignment
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add event listener to file input to show selected filename
                const fileInput = assessmentDetailsModal.querySelector('#file-upload');
                const fileNameDisplay = assessmentDetailsModal.querySelector('.selected-file-name');
                const fileUploadLabel = assessmentDetailsModal.querySelector('.file-upload-label');
                
                if (fileInput && fileNameDisplay) {
                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files.length > 0) {
                            fileNameDisplay.textContent = this.files[0].name;
                            fileNameDisplay.classList.add('file-selected');
                        } else {
                            fileNameDisplay.textContent = 'No file selected';
                            fileNameDisplay.classList.remove('file-selected');
                        }
                    });
                    
                    // Add drag and drop functionality
                    if (fileUploadLabel) {
                        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                            fileUploadLabel.addEventListener(eventName, preventDefault, false);
                        });
                        
                        function preventDefault(e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        
                        ['dragenter', 'dragover'].forEach(eventName => {
                            fileUploadLabel.addEventListener(eventName, function() {
                                fileUploadLabel.classList.add('highlight');
                            });
                        });
                        
                        ['dragleave', 'drop'].forEach(eventName => {
                            fileUploadLabel.addEventListener(eventName, function() {
                                fileUploadLabel.classList.remove('highlight');
                            });
                        });
                        
                        fileUploadLabel.addEventListener('drop', function(e) {
                            const dt = e.dataTransfer;
                            const files = dt.files;
                            
                            if (files.length) {
                                fileInput.files = files;
                                fileNameDisplay.textContent = files[0].name;
                                fileNameDisplay.classList.add('file-selected');
                            }
                        });
                    }
                }
                
                // Add event listener to the submission form
                const submissionForm = assessmentDetailsModal.querySelector('#submission-form');
                if (submissionForm) {
                    submissionForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        submitAssessment(assessment.id, this);
                    });
                }
                
                // Ensure modal is still at front with proper styling
                assessmentDetailsModal.style.zIndex = '99999';
                
                console.log('Updated assessment details modal content');
            } else {
                assessmentDetailsModal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 style="font-family: 'Merriweather', serif; font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding-left: 10px; color: white;">Error</h2>
                            <span class="close-modal" onclick="closeAssessmentDetailsModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <p class="error-message">${data.message || 'Failed to load assessment details'}</p>
                        </div>
                    </div>
                `;
                
                showNotification(data.message || 'Failed to load assessment details', 'error');
                console.error('Failed to load assessment details:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading assessment details:', error);
            
            assessmentDetailsModal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 style="font-family: 'Merriweather', serif; font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding-left: 10px; color: white;">Error</h2>
                        <span class="close-modal" onclick="closeAssessmentDetailsModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p class="error-message">An error occurred while loading assessment details: ${error.message}</p>
                    </div>
                </div>
            `;
            
            showNotification(`An error occurred while loading assessment details: ${error.message}`, 'error');
        });
}

// Function to close assessment details modal
function closeAssessmentDetailsModal() {
    console.log('Closing assessment details modal');
    const assessmentDetailsModal = document.getElementById('assessmentDetailsModal');
    if (assessmentDetailsModal) {
        assessmentDetailsModal.style.display = 'none';
    }
}

// Function to submit assessment
function submitAssessment(assessmentId, form) {
    console.log('Submitting assessment:', assessmentId);
    
    // Show loading state
    const submitBtn = form.querySelector('.submit-assessment-btn');
    const fileInput = form.querySelector('#file-upload');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        showNotification('Please select a file to submit', 'error');
        return;
    }
    
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;
        
        // Create FormData object
        const formData = new FormData(form);
        
        // Send the file to the server
        fetch('submit_assessment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Submission response:', data);
            
            // Always show a notification (even if there's an error in the response)
            if (data.success) {
                // Create an immediately visible notification before closing the modal
                const container = document.createElement('div');
                container.style.position = 'fixed';
                container.style.top = '0';
                container.style.left = '0';
                container.style.width = '100%';
                container.style.height = '100%';
                container.style.zIndex = '100000';
                container.style.display = 'flex';
                container.style.justifyContent = 'center';
                container.style.alignItems = 'flex-start';
                container.style.paddingTop = '100px';
                
                const message = document.createElement('div');
                message.style.backgroundColor = 'rgba(46, 204, 113, 0.95)';
                message.style.color = 'white';
                message.style.padding = '20px 30px';
                message.style.borderRadius = '8px';
                message.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.3)';
                message.style.fontSize = '18px';
                message.style.fontWeight = 'bold';
                message.style.borderLeft = '5px solid #27ae60';
                message.innerHTML = `<strong>${data.message || "Assignment Successfully Submitted!"}</strong>`;
                
                container.appendChild(message);
                document.body.appendChild(container);
                
                // Close modal and refresh view after a short delay
                setTimeout(() => {
                    closeAssessmentDetailsModal();
                    
                    // Reload assessment details to show the submission
                    viewAssessmentDetails(assessmentId);
                    
                    // Remove the notification after a few seconds
                    setTimeout(() => {
                        container.style.opacity = '0';
                        container.style.transition = 'opacity 0.5s';
                        setTimeout(() => container.remove(), 500);
                    }, 3000);
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to submit assessment');
            }
        })
        .catch(error => {
            console.error('Error submitting assessment:', error);
            showNotification(`Error submitting assessment: ${error.message}`, 'error');
            
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
}

// Function to unsubmit assessment
function unsubmitAssessment(assessmentId) {
    console.log('Unsubmitting assessment:', assessmentId);
    
    // Get the button that was clicked
    const button = document.querySelector(`.unsubmit-btn[onclick*="${assessmentId}"]`);
    
    // Check if button exists and if assignment is graded
    // This is a fallback check in case someone tries to call this function directly
    const isButtonGraded = button && button.getAttribute('data-graded') === 'true';
    
    // If there's no button or the button indicates graded status, show warning popup
    if (!button || isButtonGraded) {
        showGradedAssignmentPopup();
        return;
    }
    
    // Proceed with normal unsubmit flow for non-graded submissions
    // Confirm before unsubmitting
    if (!confirm("Are you sure you want to unsubmit this assignment? This action cannot be undone.")) {
        return; // Exit if the user cancels
    }
    
    // Show loading state
    const unsubmitBtn = button;
    const originalText = unsubmitBtn.innerHTML;
    unsubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Unsubmitting...';
    unsubmitBtn.disabled = true;

    // Create FormData and append assessment ID
    const formData = new FormData();
    formData.append('assessment_id', assessmentId);

    // Send request to unsubmit assessment
    fetch('unsubmit_assessment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Create an immediately visible notification
            showNotification(data.message || "Assignment Unsubmitted", "success");
            
            // Close modal and refresh view after a short delay
            setTimeout(() => {
                closeAssessmentDetailsModal();
                
                // Reload assessment details to show the unsubmitted state
                viewAssessmentDetails(assessmentId);
            }, 1500);
        } else {
            console.error('Unsubmit assessment error:', data.error);
            
            // If the error is about being graded, show the specialized popup
            if (data.error && data.error.includes("graded")) {
                showGradedAssignmentPopup();
            } else {
            showNotification(data.message || 'Failed to unsubmit assessment', 'error');
            }
            
            // Reset button state
            unsubmitBtn.innerHTML = originalText;
            unsubmitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error unsubmitting assessment:', error);
        showNotification(`Error unsubmitting assessment: ${error.message}`, 'error');
        
        // Reset button state
        unsubmitBtn.innerHTML = originalText;
        unsubmitBtn.disabled = false;
    });
}

// Function to show popup for graded assignments
function showGradedAssignmentPopup() {
    const popupContainer = document.createElement('div');
    popupContainer.style.position = 'fixed';
    popupContainer.style.top = '0';
    popupContainer.style.left = '0';
    popupContainer.style.width = '100%';
    popupContainer.style.height = '100%';
    popupContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    popupContainer.style.display = 'flex';
    popupContainer.style.justifyContent = 'center';
    popupContainer.style.alignItems = 'center';
    popupContainer.style.zIndex = '100001';
    
    const popupContent = document.createElement('div');
    popupContent.style.backgroundColor = 'white';
    popupContent.style.padding = '20px';
    popupContent.style.borderRadius = '8px';
    popupContent.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.3)';
    popupContent.style.maxWidth = '500px';
    popupContent.style.width = '90%';
    popupContent.style.textAlign = 'center';
    popupContent.style.position = 'relative';
    
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.position = 'absolute';
    closeButton.style.right = '10px';
    closeButton.style.top = '10px';
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.fontSize = '20px';
    closeButton.style.cursor = 'pointer';
    closeButton.style.color = '#555';
    closeButton.onclick = function() {
        document.body.removeChild(popupContainer);
    };
    
    const icon = document.createElement('div');
    icon.innerHTML = '<i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 15px;"></i>';
    
    const title = document.createElement('h3');
    title.textContent = 'Cannot Unsubmit Graded Assignment';
    title.style.color = '#2c3e50';
    title.style.marginBottom = '15px';
    
    const message = document.createElement('p');
    message.textContent = 'You cannot unsubmit this assignment because it has already been graded by your teacher. Graded assignments are final and cannot be modified.';
    message.style.color = '#7f8c8d';
    message.style.lineHeight = '1.6';
    message.style.marginBottom = '20px';
    
    const okButton = document.createElement('button');
    okButton.textContent = 'OK';
    okButton.style.backgroundColor = '#3498db';
    okButton.style.color = 'white';
    okButton.style.border = 'none';
    okButton.style.padding = '10px 25px';
    okButton.style.borderRadius = '5px';
    okButton.style.cursor = 'pointer';
    okButton.style.fontWeight = 'bold';
    okButton.style.fontSize = '14px';
    okButton.onclick = function() {
        document.body.removeChild(popupContainer);
    };
    
    popupContent.appendChild(closeButton);
    popupContent.appendChild(icon);
    popupContent.appendChild(title);
    popupContent.appendChild(message);
    popupContent.appendChild(okButton);
    popupContainer.appendChild(popupContent);
    document.body.appendChild(popupContainer);
    
    // Add fade-in animation
    popupContainer.style.opacity = '0';
    popupContainer.style.transition = 'opacity 0.3s ease';
    setTimeout(() => {
        popupContainer.style.opacity = '1';
    }, 10);
}

// Function to confirm leaving a class
function confirmLeaveClass(classId, className) {
    // Prevent event propagation
    event.preventDefault();
    event.stopPropagation();
    
    console.log(`Confirming leave class: ${className} (ID: ${classId})`);
    
    // Set class name in confirmation modal
    document.getElementById('leaveClassName').textContent = className;
    
    // Show confirmation modal
    const confirmationModal = document.getElementById('leaveClassConfirmationModal');
    confirmationModal.classList.add('active');
    
    // Set up the confirm button click handler
    const confirmButton = document.getElementById('confirmLeaveClassBtn');
    
    // Remove any existing event listeners
    const newConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
    
    // Add new event listener
    newConfirmButton.addEventListener('click', function() {
        leaveClass(classId);
    });
}

// Function to close leave class confirmation modal
function closeLeaveClassModal() {
    const confirmationModal = document.getElementById('leaveClassConfirmationModal');
    confirmationModal.classList.remove('active');
}

// Function to leave a class
function leaveClass(classId) {
    console.log(`Leaving class with ID: ${classId}`);
    
    // Disable confirm button and show loading state
    const confirmButton = document.getElementById('confirmLeaveClassBtn');
    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leaving...';
    confirmButton.disabled = true;
    
    // Send request to leave class
    console.log(`Sending request to leave class with ID: ${classId}`);
    console.log(`Request body: ${JSON.stringify({class_id: classId})}`);
    
    fetch('leave_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: classId
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Clone the response so we can check the text and still process it as JSON
        return response.text().then(text => {
            console.log('Raw response text:', text);
            
            // Now try to parse it as JSON
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error(`Unexpected token '<', "${text.substring(0, 50)}..." is not valid JSON`);
            }
        });
    })
    .then(data => {
        console.log('Processed response data:', data);
        if (data.success) {
            // Close the confirmation modal
            closeLeaveClassModal();
            
            // Create animated notification
            showNotification('You have successfully left the class', 'success');
            
            // Remove the class from the UI with animation
            const classItem = document.querySelector(`.class-item[data-class-id="${classId}"]`);
            if (classItem) {
                classItem.style.transition = 'all 0.5s ease';
                classItem.style.transform = 'translateY(-20px)';
                classItem.style.opacity = '0';
                
                setTimeout(() => {
                    classItem.remove();
                    
                    // Check if there are any classes left, if not, show the "no classes" message
                    const classesList = document.querySelector('.classes-list');
                    if (classesList && classesList.children.length === 0) {
                        classesList.innerHTML = `
                            <div class="no-classes">
                                <p>You haven't joined any classes yet.</p>
                                <p>Click the "Join Class" button to get started!</p>
                            </div>
                        `;
                    }
                }, 500);
            } else {
                // If the class item can't be found, reload all classes
                loadClasses();
            }
        } else {
            console.error('Leave class error:', data.message);
            showNotification(data.message || 'Failed to leave class', 'error');
            
            // Reset button state
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error leaving class:', error);
        showNotification(`Error leaving class: ${error.message}`, 'error');
        
        // Reset button state
        confirmButton.innerHTML = originalText;
        confirmButton.disabled = false;
        
        // Close the confirmation modal
        closeLeaveClassModal();
    });
}

// Function to set up navigation
function setupNavigation() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1);
            navigateToSection(sectionId);
            
            // Update active state
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Function to set up message handlers
function setupMessageHandlers() {
    const messageItems = document.querySelectorAll('.message-item');
    messageItems.forEach(item => {
        item.addEventListener('click', function() {
            const messageInfo = this.querySelector('.message-info');
            const sender = messageInfo.querySelector('h3').textContent;
            const subject = messageInfo.querySelector('p').textContent;
            
            // In a real application, this would open the message
            alert(`Opening message from ${sender}\nSubject: ${subject}`);
        });
    });
}

// Function to set up class handlers
function setupClassHandlers() {
    // Remove click handlers from class items - no longer needed
    const classItems = document.querySelectorAll('.class-item');
    classItems.forEach(item => {
        // Remove any existing click handlers by cloning and replacing the element
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
    });
}

// Function to load dashboard data for selected academic year
function loadDashboardData(academicYear) {
    fetch(`get-dashboard-data.php?academic_year=${academicYear}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardContent(data);
            } else {
                console.error('Error loading dashboard data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

// Modal functionality
const joinClassModal = document.getElementById('joinClassModal');
const joinClassForm = document.getElementById('joinClassForm');
const closeModal = document.querySelector('.close-modal');

// Show modal when Join Class button is clicked
function handleJoinClass() {
    joinClassModal.style.display = 'block';
}

// Close modal when X is clicked
closeModal.onclick = function() {
    joinClassModal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == joinClassModal) {
        joinClassModal.style.display = 'none';
    }
    
    const lessonDetailsModal = document.getElementById('lessonDetailsModal');
    if (lessonDetailsModal && event.target == lessonDetailsModal) {
        lessonDetailsModal.style.display = 'none';
    }
    
    const classDetailsModal = document.getElementById('classDetailsModal');
    if (classDetailsModal && event.target == classDetailsModal) {
        classDetailsModal.style.display = 'none';
    }
}

// Handle form submission
joinClassForm.onsubmit = function(event) {
    event.preventDefault();
    
    const classCode = document.getElementById('classCode').value;
    
    // Disable submit button and show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining Class...';
    submitButton.disabled = true;

    // Send request to join class
    fetch('join_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            classCode: classCode
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('Successfully joined the class!');
            // Close modal and reset form
            joinClassModal.style.display = 'none';
            joinClassForm.reset();
            // Refresh the classes list
            loadClasses();
        } else {
            console.error('Join class error:', data.debug);
            showNotification(data.message || 'Failed to join class', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while joining the class. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Notification function
function showNotification(message, type = 'success') {
    console.log('Showing notification:', message, type);
    
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `<strong>${message}</strong>`;
    
    // Use the notification container if it exists, otherwise fallback to body
    const container = document.getElementById('notification-container') || document.body;
    container.appendChild(notification);
    
    // Set styles programmatically to ensure they're applied
    notification.style.position = 'fixed';
    notification.style.top = '100px';
    notification.style.left = '50%';
    notification.style.transform = 'translateX(-50%)';
    notification.style.zIndex = '99999'; // Extremely high z-index to ensure visibility
    notification.style.minWidth = '350px';
    notification.style.textAlign = 'center';
    notification.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.3)';
    notification.style.padding = '20px 30px';
    notification.style.borderRadius = '8px';
    notification.style.color = 'white';
    notification.style.fontWeight = '500';
    notification.style.fontSize = '18px';
    notification.style.opacity = '0';
    notification.style.transition = 'opacity 0.3s ease-in-out';
    
    // Show notification with delayed animation to ensure it displays
    setTimeout(() => {
        notification.style.display = 'block';
        notification.style.opacity = '1';
        notification.style.animation = 'pulseNotification 2s infinite';
        console.log('Notification should be visible now');
    }, 100);
    
    // Hide and remove notification after 5 seconds (increased from 3)
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 5000);
}

// Initial load of classes
document.addEventListener('DOMContentLoaded', function() {
    loadClasses();
}); 

// Function to close join class modal
function closeJoinClassModal() {
    const joinClassModal = document.getElementById('joinClassModal');
    if (joinClassModal) {
        joinClassModal.style.display = 'none';
    }
}

// Function to load class details
function loadClassDetails(classId) {
    const lessonsList = document.getElementById('class-lessons-list');
    const assessmentsList = document.getElementById('class-assessments-list');
    
    if (lessonsList) {
        lessonsList.innerHTML = '<p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading lessons...</p>';
    }
    
    if (assessmentsList) {
        assessmentsList.innerHTML = '<p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading assessments...</p>';
    }
    
    console.log(`Fetching class details for class ID: ${classId}`);
    
    // Fetch class details from the server
    fetch(`get_student_class_details.php?class_id=${classId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Class details data:', data);
            
            if (data.success) {
                // Update lessons list
                if (lessonsList) {
                    if (!data.lessons || data.lessons.length === 0) {
                        lessonsList.innerHTML = '<p>No lessons available for this class yet.</p>';
                    } else {
                        console.log('Rendering lessons:', data.lessons);
                        lessonsList.innerHTML = data.lessons.map(lesson => `
                            <div class="lesson-item" data-lesson-id="${lesson.id}" onclick="viewLessonDetails(${lesson.id})" style="cursor: pointer;">
                                <h4>${lesson.title}</h4>
                                <p>${lesson.description}</p>
                                <div class="meta-info">
                                <p class="lesson-date"><i class="fas fa-calendar"></i> Added: ${new Date(lesson.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        `).join('');
                        
                        console.log('Adding click listeners to lesson items');
                        // Add click event listeners to all lesson items
                        const lessonItems = document.querySelectorAll('.lesson-item');
                        console.log('Found lesson items:', lessonItems.length);
                        lessonItems.forEach(item => {
                            console.log('Adding click listener to lesson item with ID:', item.getAttribute('data-lesson-id'));
                            item.addEventListener('click', function(event) {
                                console.log('Lesson item clicked!');
                                const lessonId = this.getAttribute('data-lesson-id');
                                console.log('Lesson ID from clicked item:', lessonId);
                                if (lessonId) {
                                    viewLessonDetails(lessonId);
                                } else {
                                    console.error('Lesson ID not found in clicked element');
                                }
                            });
                        });
                    }
                }
                
                // Update assessments list
                if (assessmentsList) {
                    if (!data.assessments || data.assessments.length === 0) {
                        assessmentsList.innerHTML = '<p>No assessments available for this class yet.</p>';
                    } else {
                        console.log('Rendering assessments:', data.assessments);
                        assessmentsList.innerHTML = data.assessments.map(assessment => `
                            <div class="assessment-item" data-assessment-id="${assessment.id}" onclick="viewAssessmentDetails(${assessment.id})" style="cursor: pointer;">
                                <h4>${assessment.title}</h4>
                                <p>${assessment.description}</p>
                                <div class="meta-info">
                                ${assessment.due_date ? 
                                    `<p class="due-date"><i class="fas fa-clock"></i> Due: ${new Date(assessment.due_date).toLocaleDateString()}</p>` : 
                                    '<p class="due-date"><i class="fas fa-clock"></i> No due date set</p>'}
                                    <p class="score-info"><i class="fas fa-star"></i> Total Points: ${assessment.max_score}</p>
                                    ${assessment.student_submission && assessment.student_submission.score !== null ? 
                                        `<p class="submitted-score"><i class="fas fa-check-circle"></i> Your Score: ${assessment.student_submission.score}/${assessment.max_score}</p>` : 
                                        assessment.student_submission ? 
                                            `<p class="submitted-status"><i class="fas fa-hourglass-half"></i> Submitted - Waiting for grade</p>` :
                                            ''}
                                </div>
                            </div>
                        `).join('');
                        
                        console.log('Adding click listeners to assessment items');
                        // Add click event listeners to all assessment items
                        const assessmentItems = document.querySelectorAll('.assessment-item');
                        console.log('Found assessment items:', assessmentItems.length);
                        assessmentItems.forEach(item => {
                            console.log('Adding click listener to assessment item with ID:', item.getAttribute('data-assessment-id'));
                            item.addEventListener('click', function(event) {
                                console.log('Assessment item clicked!');
                                const assessmentId = this.getAttribute('data-assessment-id');
                                console.log('Assessment ID from clicked item:', assessmentId);
                                if (assessmentId) {
                                    viewAssessmentDetails(assessmentId);
                                } else {
                                    console.error('Assessment ID not found in clicked element');
                                }
                            });
                        });
                    }
                }
            } else {
                console.error('Failed to load class details:', data.message, data.error);
                
                if (lessonsList) {
                    lessonsList.innerHTML = `<p>Failed to load lessons: ${data.error || 'Unknown error'}</p>`;
                }
                
                if (assessmentsList) {
                    assessmentsList.innerHTML = `<p>Failed to load assessments: ${data.error || 'Unknown error'}</p>`;
                }
                
                showNotification(data.message || 'Failed to load class details', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading class details:', error);
            
            if (lessonsList) {
                lessonsList.innerHTML = `<p>Failed to load lessons. Please try again later. Error: ${error.message}</p>`;
            }
            
            if (assessmentsList) {
                assessmentsList.innerHTML = `<p>Failed to load assessments. Please try again later. Error: ${error.message}</p>`;
            }
            
            showNotification(`An error occurred while loading class details: ${error.message}`, 'error');
        });
}

// Function to view lesson details
function viewLessonDetails(lessonId) {
    console.log('Viewing lesson details for lesson ID:', lessonId);
    
    // Create the lesson details modal dynamically if it doesn't exist
    let lessonDetailsModal = document.getElementById('lessonDetailsModal');
    
    if (!lessonDetailsModal) {
        // Create modal element
        lessonDetailsModal = document.createElement('div');
        lessonDetailsModal.id = 'lessonDetailsModal';
        lessonDetailsModal.className = 'modal';
        
        // Add it to the body
        document.body.appendChild(lessonDetailsModal);
        console.log('Created new lesson details modal');
    }
    
    // Show loading state
    lessonDetailsModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Loading Lesson...</h2>
                <span class="close-modal" onclick="closeLessonDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading lesson details...</p>
            </div>
        </div>
    `;
    
    // Ensure modal has highest z-index and is visible
    lessonDetailsModal.style.display = 'block';
    lessonDetailsModal.style.zIndex = '99999';
    
    // Bring modal to front
    document.body.appendChild(lessonDetailsModal);
    
    console.log('Showing lesson details modal');
    
    // Fetch lesson details
    fetch(`get_student_lesson_details.php?id=${lessonId}`)
        .then(response => {
            console.log('Lesson details API response status:', response.status);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Lesson details data:', data);
            
            if (data.success) {
                const lesson = data.data;
                console.log('Lesson data:', lesson);
                
                // Update modal content
                lessonDetailsModal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>${lesson.title || 'Lesson Details'}</h2>
                            <span class="close-modal" onclick="closeLessonDetailsModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="lesson-details">
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-book"></i> Class:</span>
                                    <span class="detail-value">${lesson.class_name || 'Unknown Class'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><i class="fas fa-calendar"></i> Added:</span>
                                    <span class="detail-value">${lesson.created_at ? new Date(lesson.created_at).toLocaleDateString() : 'Unknown date'}</span>
                                </div>
                                
                                <div class="lesson-content">
                                    <h3><i class="fas fa-info-circle"></i> Description</h3>
                                    <div class="lesson-description">
                                        <p>${lesson.description || 'No description provided.'}</p>
                                    </div>
                                    
                                    <h3><i class="fas fa-file"></i> Files</h3>
                                    <div class="lesson-files">
                                        ${lesson.files && lesson.files.length > 0 ? 
                                            lesson.files.map(file => `
                                                <div class="file-item">
                                                    <i class="fas fa-file-alt"></i>
                                                    <span class="file-name">${file.file_name}</span>
                                                    <a href="${file.file_path}" class="file-download">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                            `).join('') : 
                                            '<p>No files attached to this lesson.</p>'
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Ensure modal is still at front with proper styling
                lessonDetailsModal.style.zIndex = '99999';
                
                console.log('Updated lesson details modal content');
            } else {
                lessonDetailsModal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Error</h2>
                            <span class="close-modal" onclick="closeLessonDetailsModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <p class="error-message">${data.message || 'Failed to load lesson details'}</p>
                        </div>
                    </div>
                `;
                
                showNotification(data.message || 'Failed to load lesson details', 'error');
                console.error('Failed to load lesson details:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading lesson details:', error);
            
            lessonDetailsModal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Error</h2>
                        <span class="close-modal" onclick="closeLessonDetailsModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p class="error-message">An error occurred while loading lesson details: ${error.message}</p>
                    </div>
                </div>
            `;
            
            showNotification(`An error occurred while loading lesson details: ${error.message}`, 'error');
        });
}

// Function to close lesson details modal
function closeLessonDetailsModal() {
    console.log('Closing lesson details modal');
    const lessonDetailsModal = document.getElementById('lessonDetailsModal');
    if (lessonDetailsModal) {
        lessonDetailsModal.style.display = 'none';
    }
}

// Function to close the class details modal
function closeClassDetailsModal() {
    const classDetailsModal = document.getElementById('classDetailsModal');
    if (classDetailsModal) {
        classDetailsModal.style.display = 'none';
        
        // End the class view session
        endClassViewLog();
    }
}

// Function to load notifications
function loadNotifications() {
    if (notificationsLoaded) return;
    
    const notificationsList = document.querySelector('.notifications-list');
    
    // Show loading state
    notificationsList.innerHTML = `
        <div class="loading-message">
            <i class="fas fa-spinner fa-spin"></i> Loading notifications...
        </div>
    `;
    
    fetch('get_student_notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications data:', data); // Debug log
            
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                displayNotifications(data.notifications);
                notificationsLoaded = true;
            } else {
                notificationsList.innerHTML = `
                    <div class="empty-notifications">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--dark-gray); display: block; margin-bottom: 1rem;"></i>
                        <p>Error loading notifications</p>
                        <p style="font-size: 0.85rem; margin-top: 0.5rem;">${data.message}</p>
                    </div>
                `;
                console.error('Error loading notifications:', data.message, data.error_details);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationsList.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--dark-gray); display: block; margin-bottom: 1rem;"></i>
                    <p>Error loading notifications</p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">Please try again later</p>
                </div>
            `;
        });
}

// Function to display notifications
function displayNotifications(notifications) {
    const notificationsList = document.querySelector('.notifications-list');
    
    if (!notifications || notifications.length === 0) {
        notificationsList.innerHTML = `
            <div class="empty-notifications">
                <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--dark-gray); display: block; margin-bottom: 1rem;"></i>
                <p>No new notifications</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem;">You're all caught up!</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    notifications.forEach(notification => {
        // Choose icon based on notification type
        let icon = 'bell';
        if (notification.type === 'lesson') icon = 'book';
        if (notification.type === 'assessment') icon = 'tasks';
        if (notification.type === 'message') icon = 'envelope';
        if (notification.type === 'class_message') icon = 'comments';
        if (notification.type === 'system') icon = 'info-circle';
        
        html += `
            <div class="notification-item${notification.is_read ? '' : ' unread'}" 
                data-id="${notification.id}" 
                data-type="${notification.type}" 
                data-reference-id="${notification.reference_id || ''}" 
                data-class-id="${notification.class_id || ''}"
                onclick="handleNotificationClick(this)">
                <div class="notification-icon">
                    <i class="fas fa-${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-text">${notification.message}</div>
                    <div class="notification-time">${notification.formatted_time}</div>
                </div>
            </div>
        `;
    });
    
    notificationsList.innerHTML = html;
}

// Function to update notification badge
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Function to mark a single notification as read
function markNotificationAsRead(notificationId) {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update unread count in notification badge
            const badge = document.querySelector('.notification-badge');
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
                badge.textContent = currentCount - 1;
            } else {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Function to mark all notifications as read
function markAllNotificationsAsRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Hide notification badge
            const badge = document.querySelector('.notification-badge');
            badge.style.display = 'none';
            
            showNotification('All notifications marked as read', 'success');
        } else {
            showNotification('Error marking notifications as read', 'error');
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        showNotification('Error marking notifications as read', 'error');
    });
}

// Function to handle notification clicks
function handleNotificationClick(notificationElement) {
    const notificationId = notificationElement.getAttribute('data-id');
    const notificationType = notificationElement.getAttribute('data-type');
    const referenceId = notificationElement.getAttribute('data-reference-id');
    const classId = notificationElement.getAttribute('data-class-id');
    
    console.log(`Clicked notification: ${notificationType}, ID: ${notificationId}, Reference: ${referenceId}, Class: ${classId}`);
    
    // Mark notification as read
    markNotificationAsRead(notificationId);
    notificationElement.classList.remove('unread');
    
    // Close the notifications popup
    document.getElementById('notificationsPopup').classList.remove('active');
    
    // Navigate based on notification type
    switch(notificationType) {
        case 'lesson':
            if (referenceId) {
                // Open lesson details
                viewLessonDetails(referenceId);
            } else if (classId) {
                // If no specific lesson, just view the class
                fetch(`get_class_details.php?class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viewClass(classId, data.class_name, data.teacher_name);
                        } else {
                            showNotification('Could not find class details', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching class details:', error);
                        showNotification('Error loading class details', 'error');
                    });
            }
            break;
            
        case 'assessment':
            if (referenceId) {
                // Open assessment details
                viewAssessmentDetails(referenceId);
            } else if (classId) {
                // If no specific assessment, just view the class
                fetch(`get_class_details.php?class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viewClass(classId, data.class_name, data.teacher_name);
                        } else {
                            showNotification('Could not find class details', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching class details:', error);
                        showNotification('Error loading class details', 'error');
                    });
            }
            break;
            
        case 'message':
            if (classId) {
                console.log('DEBUG: Processing message notification with classId:', classId);
                
                // Add direct debug method - bypass normal flow to debug
                console.log('DEBUG: Attempting direct method...');
                getTeacherIdForClass(classId)
                    .then(teacherId => {
                        console.log('DEBUG: Got teacher ID directly:', teacherId);
                        
                        // Get teacher name for display
                        return fetch(`get_class_teacher.php?class_id=${classId}`)
                            .then(response => {
                                console.log('DEBUG: Teacher API response status:', response.status);
                                return response.json();
                            })
                            .then(data => {
                                console.log('DEBUG: Teacher data:', data);
                                if (data.success) {
                                    console.log('DEBUG: Calling openTeacherChat with:', classId, data.teacher_name);
                                    // Manually set up teacher chat
                                    manualOpenTeacherChat(teacherId, classId, data.teacher_name);
                                } else {
                                    throw new Error(data.message || 'Failed to get teacher name');
                                }
                            });
                    })
                    .catch(error => {
                        console.error('DEBUG ERROR:', error);
                        showNotification('Debug error: ' + error.message, 'error');
                    });
            } else {
                showNotification('Cannot open teacher chat - missing class information', 'error');
            }
            break;
            
        case 'class_message':
            if (classId) {
                // Get class details
                fetch(`get_class_details.php?class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Open class chat
                            openClassChat(classId, data.class_name);
                        } else {
                            showNotification('Could not find class details', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching class details:', error);
                        showNotification('Error loading class chat', 'error');
                    });
            }
            break;
            
        case 'system':
            // System notifications might not have specific actions
            showNotification('System notification acknowledged', 'success');
            break;
            
        default:
            console.log('Unknown notification type:', notificationType);
            showNotification('Unknown notification type', 'error');
    }
}

// Manual teacher chat opener - bypass the getTeacherIdForClass step
function manualOpenTeacherChat(teacherId, classId, teacherName) {
    console.log('DEBUG: Manual chat opener with teacherId:', teacherId, 'classId:', classId, 'teacherName:', teacherName);
    
    try {
        // Clear any existing polling interval
        if (currentTeacherChat.messagePollingInterval) {
            clearInterval(currentTeacherChat.messagePollingInterval);
        }
        
        // Set current chat details
        currentTeacherChat.teacherId = teacherId;
        currentTeacherChat.classId = classId;
        currentTeacherChat.teacherName = teacherName;
        currentTeacherChat.messagesContainer = document.getElementById('teacherChatMessages');
        
        console.log('DEBUG: Current teacher chat object:', {...currentTeacherChat});
        
        // Verify DOM elements exist
        const chatTitle = document.getElementById('teacherChatTitle');
        const chatWindow = document.getElementById('teacherChatWindow');
        const chatOverlay = document.getElementById('chatOverlay');
        
        console.log('DEBUG: DOM elements exist?', 
            'chatTitle:', !!chatTitle, 
            'chatWindow:', !!chatWindow, 
            'chatOverlay:', !!chatOverlay);
        
        // Update chat title
        if (chatTitle) {
            chatTitle.textContent = `Chat with ${teacherName}`;
        } else {
            console.error('DEBUG: teacherChatTitle element not found');
        }
        
        // Show chat window and overlay
        if (chatWindow) {
            chatWindow.classList.add('active');
        } else {
            console.error('DEBUG: teacherChatWindow element not found');
        }
        
        if (chatOverlay) {
            chatOverlay.classList.add('active');
        } else {
            console.error('DEBUG: chatOverlay element not found');
        }
        
        // Reset messages container and show loading state
        if (currentTeacherChat.messagesContainer) {
            currentTeacherChat.messagesContainer.innerHTML = `
                <div class="chat-loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading messages...
                </div>
            `;
        } else {
            console.error('DEBUG: teacherChatMessages container not found');
        }
        
        // Load messages
        loadTeacherStudentMessages(teacherId, classId);
        
        // Start polling for new messages every 5 seconds
        currentTeacherChat.messagePollingInterval = setInterval(() => {
            loadTeacherStudentMessages(teacherId, classId, true);
        }, 5000);
        
        // Focus on input
        setTimeout(() => {
            const chatInput = document.getElementById('teacherChatInput');
            if (chatInput) {
                chatInput.focus();
            } else {
                console.error('DEBUG: teacherChatInput element not found');
            }
        }, 300);
        
        console.log('DEBUG: Manual chat open process completed');
    } catch (e) {
        console.error('DEBUG: Error in manualOpenTeacherChat:', e);
        showNotification('Error opening chat: ' + e.message, 'error');
    }
} 

// Function to view class announcements
function viewAnnouncements(classId, className) {
    // Close the class details modal first to prevent overlap
    closeClassDetailsModal();
    
    console.log(`Opening announcements for class: ${className} (ID: ${classId})`);
    
    // Update announcements title with class name
    document.getElementById('announcementsTitle').textContent = `${className} Announcements`;
    
    // Show announcements window
    const announcementsWindow = document.getElementById('announcementsWindow');
    announcementsWindow.classList.add('active');
    
    // Add overlay to allow clicking outside to close
    document.getElementById('chatOverlay').classList.add('active');
    
    // Show loading state in the announcements content
    const announcementsContent = document.getElementById('announcementsContent');
    announcementsContent.innerHTML = `
        <div class="announcements-loading">
            <i class="fas fa-spinner fa-spin"></i> Loading announcements...
        </div>
    `;
    
    // Fetch real announcements from the server
    fetch(`get_announcements.php?class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAnnouncements(announcementsContent, data.announcements, className);
            } else {
                throw new Error(data.message || 'Failed to load announcements');
            }
        })
        .catch(error => {
            console.error('Error loading announcements:', error);
            announcementsContent.innerHTML = `
                <div class="empty-announcements">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #e74c3c; display: block; margin-bottom: 1rem;"></i>
                    <p>Error loading announcements</p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--dark-gray);">
                        ${error.message || 'Please try again later.'}
                    </p>
                </div>
            `;
        });
}

// Function to display real announcements
function displayAnnouncements(container, announcements, className) {
    if (!announcements || announcements.length === 0) {
        container.innerHTML = `
            <div class="empty-announcements">
                <i class="fas fa-bullhorn" style="font-size: 3rem; color: var(--dark-gray); display: block; margin-bottom: 1rem;"></i>
                <p>No announcements yet</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--dark-gray);">
                    Announcements for ${className} will appear here when they are posted.
                </p>
            </div>
        `;
        return;
    }
    
    // Start building the HTML content
    let html = '';
    
    // Sort announcements by priority and date
    const sortedAnnouncements = [...announcements].sort((a, b) => {
        // First sort by priority (urgent > important > normal)
        const priorityOrder = { 'urgent': 0, 'important': 1, 'normal': 2 };
        if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
            return priorityOrder[a.priority] - priorityOrder[b.priority];
        }
        // Then sort by date (newest first)
        return new Date(b.created_at) - new Date(a.created_at);
    });
    
    // Generate the announcements HTML
    sortedAnnouncements.forEach(announcement => {
        // Define priority classes and icons
        let priorityClass = '';
        let priorityIcon = '';
        
        switch(announcement.priority) {
            case 'urgent':
                priorityClass = 'priority-urgent';
                priorityIcon = '<i class="fas fa-exclamation-circle" title="Urgent"></i>';
                break;
            case 'important':
                priorityClass = 'priority-important';
                priorityIcon = '<i class="fas fa-exclamation" title="Important"></i>';
                break;
            default:
                priorityClass = 'priority-normal';
                priorityIcon = '';
        }
        
        // Format the date
        const announcementDate = new Date(announcement.created_at);
        const formattedDate = announcementDate.toLocaleDateString('en-US', {
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Create the announcement item HTML
        html += `
            <div class="announcement-item ${priorityClass}">
                <div class="announcement-title">
                    ${priorityIcon} ${escapeHtml(announcement.title)}
                </div>
                <div class="announcement-date">
                    <i class="fas fa-clock"></i> ${formattedDate} by ${escapeHtml(announcement.teacher_name)}
                </div>
                <div class="announcement-content">
                    ${escapeHtml(announcement.content).replace(/\n/g, '<br>')}
                </div>`;
        
        // Add files if any
        if (announcement.files && announcement.files.length > 0) {
            html += `<div class="announcement-files">`;
            announcement.files.forEach(file => {
                // Determine appropriate icon based on file type
                let fileIcon = 'fa-file';
                if (file.file_type) {
                    if (file.file_type.includes('image')) fileIcon = 'fa-file-image';
                    else if (file.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                    else if (file.file_type.includes('word') || file.file_type.includes('doc')) fileIcon = 'fa-file-word';
                    else if (file.file_type.includes('video')) fileIcon = 'fa-file-video';
                }
                
                // Format file size (convert bytes to appropriate unit)
                let fileSize = file.file_size;
                let fileSizeUnit = 'B';
                if (fileSize > 1024) {
                    fileSize = (fileSize / 1024).toFixed(1);
                    fileSizeUnit = 'KB';
                }
                if (fileSize > 1024) {
                    fileSize = (fileSize / 1024).toFixed(1);
                    fileSizeUnit = 'MB';
                }
                
                html += `
                    <div class="file-item">
                        <i class="fas ${fileIcon}"></i>
                        <span class="file-name">${escapeHtml(file.file_name)}</span>
                        <span class="file-size">${fileSize} ${fileSizeUnit}</span>
                        <a href="${file.file_path}" class="file-download" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                `;
            });
            html += `</div>`;
        }
        
        // Close the announcement item div
        html += `</div>`;
    });
    
    // Update the container with the generated HTML
    container.innerHTML = html;
}

// Function to close announcements window
function closeAnnouncementsWindow() {
    const announcementsWindow = document.getElementById('announcementsWindow');
    announcementsWindow.classList.remove('active');
    
    // Only remove overlay if no chat windows are active
    if (!document.getElementById('teacherChatWindow').classList.contains('active') && 
        !document.getElementById('classChatWindow').classList.contains('active')) {
        document.getElementById('chatOverlay').classList.remove('active');
    }
}

// Function to display sample announcements (for demo purposes)
// This is kept for backward compatibility but no longer used
function displaySampleAnnouncements(container, className) {
    container.innerHTML = `
        <div class="empty-announcements">
            <i class="fas fa-bullhorn" style="font-size: 3rem; color: var(--dark-gray); display: block; margin-bottom: 1rem;"></i>
            <p>No announcements yet</p>
            <p style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--dark-gray);">
                Announcements for ${className} will appear here when they are posted.
            </p>
        </div>
    `;
}

// Function to log class view
function logClassView(classId) {
    // If already viewing a class, end the previous log first
    if (currentClassViewLogId !== null) {
        endClassViewLog();
    }
    
    // Record the start time
    classViewStartTime = new Date();
    
    // Create form data
    const formData = new FormData();
    formData.append('class_id', classId);
    
    // Send the log request
    fetch('log_class_view.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentClassViewLogId = data.log_id;
            console.log('Class view logged successfully, log_id:', currentClassViewLogId);
        } else {
            console.error('Failed to log class view:', data.message);
        }
    })
    .catch(error => {
        console.error('Error logging class view:', error);
    });
}

// Function to end class view log
function endClassViewLog() {
    // If no active log, exit
    if (currentClassViewLogId === null || classViewStartTime === null) {
        return;
    }
    
    // Calculate duration in seconds
    const duration = Math.floor((new Date() - classViewStartTime) / 1000);
    
    // Create form data
    const formData = new FormData();
    formData.append('log_id', currentClassViewLogId);
    formData.append('duration', duration);
    
    // Send the update request
    fetch('update_class_view_duration.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Class view duration updated successfully:', duration, 'seconds');
        } else {
            console.error('Failed to update class view duration:', data.message);
        }
        
        // Reset tracking variables
        currentClassViewLogId = null;
        classViewStartTime = null;
    })
    .catch(error => {
        console.error('Error updating class view duration:', error);
        
        // Reset tracking variables even on error
        currentClassViewLogId = null;
        classViewStartTime = null;
    });
}

// Add event listener for page unload to track when user leaves
window.addEventListener('beforeunload', function() {
    // End any active class view log
    if (currentClassViewLogId !== null) {
        endClassViewLog();
    }
});