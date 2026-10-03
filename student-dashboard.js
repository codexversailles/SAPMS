// Student Dashboard JavaScript

// Function to load user data from the server
function loadUserData() {
    fetch('get-student-data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUserProfile(data.student);
                updateDashboardContent(data);
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
    document.getElementById('classChatTitle').textContent = `${className} Chat`;
    document.getElementById('classChatWindow').classList.add('active');
    document.getElementById('chatOverlay').classList.add('active');
    
    // Focus on input
    setTimeout(() => {
        document.getElementById('classChatInput').focus();
    }, 300);
}

// Function to close class chat
function closeClassChat() {
    document.getElementById('classChatWindow').classList.remove('active');
    document.getElementById('chatOverlay').classList.remove('active');
}

// Function to open teacher chat
function openTeacherChat(classId, teacherName) {
    document.getElementById('teacherChatTitle').textContent = `Chat with ${teacherName}`;
    document.getElementById('teacherChatWindow').classList.add('active');
    document.getElementById('chatOverlay').classList.add('active');
    
    // Focus on input
    setTimeout(() => {
        document.getElementById('teacherChatInput').focus();
    }, 300);
}

// Function to close teacher chat
function closeTeacherChat() {
    document.getElementById('teacherChatWindow').classList.remove('active');
    document.getElementById('chatOverlay').classList.remove('active');
}

// Function to close all chats
function closeAllChats() {
    closeClassChat();
    closeTeacherChat();
}

// Function to send message in class chat
function sendClassMessage() {
    const input = document.getElementById('classChatInput');
    const message = input.value.trim();
    
    if (message) {
        const messagesContainer = document.getElementById('classChatMessages');
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        // Add message to chat
        messagesContainer.innerHTML += `
            <div class="message sent">
                <div class="message-content">${message}</div>
                <div class="message-info">You, ${timeString}</div>
            </div>
        `;
        
        // Clear input
        input.value = '';
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Simulate response after delay (in a real app, this would be a server response)
        setTimeout(() => {
            // Just a demo response
            messagesContainer.innerHTML += `
                <div class="message received">
                    <div class="message-content">This is a demo of the class chat. In a real app, this would connect to a real-time messaging system.</div>
                    <div class="message-info">System, ${timeString}</div>
                </div>
            `;
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 1000);
    }
    
    // Focus back on input
    input.focus();
}

// Function to send message in teacher chat
function sendTeacherMessage() {
    const input = document.getElementById('teacherChatInput');
    const message = input.value.trim();
    
    if (message) {
        const messagesContainer = document.getElementById('teacherChatMessages');
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        // Add message to chat
        messagesContainer.innerHTML += `
            <div class="message sent">
                <div class="message-content">${message}</div>
                <div class="message-info">You, ${timeString}</div>
            </div>
        `;
        
        // Clear input
        input.value = '';
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Simulate teacher response after delay
        setTimeout(() => {
            // Just a demo response
            messagesContainer.innerHTML += `
                <div class="message received">
                    <div class="message-content">This is a demo of the teacher chat. In a real app, messages would be sent to the actual teacher.</div>
                    <div class="message-info">Teacher, ${timeString}</div>
                </div>
            `;
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 1500);
    }
    
    // Focus back on input
    input.focus();
}

// Function to close the class details modal
function closeClassDetailsModal() {
    const classDetailsModal = document.getElementById('classDetailsModal');
    if (classDetailsModal) {
        classDetailsModal.style.display = 'none';
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

// Function to load messages
function loadMessages() {
    // Simulate API call
    const messages = [
        {
            sender: 'Mr. Smith',
            subject: 'Mathematics Assignment',
            time: '2 hours ago',
            unread: true
        },
        {
            sender: 'Mrs. Johnson',
            subject: 'Science Project Feedback',
            time: '1 day ago',
            unread: false
        }
    ];
    
    const messagesList = document.querySelector('.messages-list');
    if (messagesList) {
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
                                                
                                                <div class="submission-actions" style="margin-top: 30px; text-align: center;">
                                                    <button class="btn btn-danger unsubmit-btn" onclick="unsubmitAssessment(${assessment.id})">
                                                        <i class="fas fa-trash-alt"></i> Unsubmit Assignment
                                                    </button>
                                                </div>
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
    
    // Confirm before unsubmitting
    if (!confirm("Are you sure you want to unsubmit this assignment? This action cannot be undone.")) {
        return; // Exit if the user cancels
    }
    
    // Show loading state
    const unsubmitBtn = document.querySelector('.unsubmit-btn');
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
            message.style.backgroundColor = 'rgba(231, 76, 60, 0.95)';
            message.style.color = 'white';
            message.style.padding = '20px 30px';
            message.style.borderRadius = '8px';
            message.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.3)';
            message.style.fontSize = '18px';
            message.style.fontWeight = 'bold';
            message.style.borderLeft = '5px solid #c0392b';
            message.innerHTML = `<strong>${data.message || "Assignment Unsubmitted"}</strong>`;
            
            container.appendChild(message);
            document.body.appendChild(container);
            
            // Close modal and refresh view after a short delay
            setTimeout(() => {
                closeAssessmentDetailsModal();
                
                // Reload assessment details to show the unsubmitted state
                viewAssessmentDetails(assessmentId);
                
                // Remove the notification after a few seconds
                setTimeout(() => {
                    container.style.opacity = '0';
                    container.style.transition = 'opacity 0.5s';
                    setTimeout(() => container.remove(), 500);
                }, 3000);
            }, 1500);
        } else {
            console.error('Unsubmit assessment error:', data.debug);
            showNotification(data.message || 'Failed to unsubmit assessment', 'error');
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
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
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