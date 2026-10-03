// Teacher Dashboard JavaScript

// Function to show a modal consistently
if (typeof showModal !== 'function') {
    window.showModal = function(modalId, keepOthersOpen = false) {
        // Hide any other open modals first (unless specified to keep them open)
        if (!keepOthersOpen) {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
                modal.classList.remove('active');
            });
        }
        
        // Show the requested modal
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with ID ${modalId} not found`);
            return;
        }
        
        modal.style.display = 'flex';
        modal.classList.add('active');
        
        // Add a class to the body to prevent scrolling
        document.body.style.overflow = 'hidden';
        
        // Determine z-index dynamically if stacking modals
        if (keepOthersOpen) {
            let maxZIndex = 2000;
            document.querySelectorAll('.modal.active').forEach(activeModal => {
                if (activeModal.id !== modalId) {
                    const zIndex = parseInt(window.getComputedStyle(activeModal).zIndex || '2000');
                    maxZIndex = Math.max(maxZIndex, zIndex);
                }
            });
            modal.style.zIndex = (maxZIndex + 10).toString();
        } else {
            modal.style.zIndex = '2000';
        }
    };
}

// Function to hide a modal consistently
if (typeof hideModal !== 'function') {
    window.hideModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with ID ${modalId} not found`);
            return;
        }
        
        modal.style.display = 'none';
        modal.classList.remove('active');
        
        // Check if there are any other active modals before restoring scrolling
        const activeModals = document.querySelectorAll('.modal.active');
        if (activeModals.length === 0) {
            // Only restore scrolling if no other modals are active
            document.body.style.overflow = '';
        }
    };
}

// Function to load user data
async function loadUserData() {
    try {
        const response = await fetch('get-teacher-data.php');
        const data = await response.json();
        
        if (data.success) {
            // Update teacher name in the dashboard
            const teacherNameElements = document.querySelectorAll('.card-title');
            teacherNameElements.forEach(element => {
                if (element.textContent.includes('Welcome Back')) {
                    element.textContent = `Welcome Back, ${data.teacher.full_name}!`;
                }
            });
            
            // Update user profile in sidebar if elements exist
            const userNameElements = document.querySelectorAll('.user-name');
            if (userNameElements.length > 0) {
                userNameElements.forEach(element => {
                    element.textContent = data.teacher.full_name;
                });
            }
            
            const userEmailElements = document.querySelectorAll('.user-email');
            if (userEmailElements.length > 0) {
                userEmailElements.forEach(element => {
                    element.textContent = data.teacher.email;
                });
            }

            // Update teacher ID if element exists
            const teacherIdElements = document.querySelectorAll('.user-id');
            if (teacherIdElements.length > 0) {
                teacherIdElements.forEach(element => {
                    element.textContent = data.teacher.teacher_id;
                });
            }
        } else {
            console.error('Failed to load user data:', data.message);
            // Redirect to login if not logged in
            if (data.message === 'Not logged in') {
                window.location.href = 'teacher-auth.html';
            }
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

// Function to load and display classes
async function loadClasses() {
    try {
        const response = await fetch('get_teacher_classes.php');
        const data = await response.json();
        
        if (data.success) {
            const classesGrid = document.querySelector('.classes-grid');
            classesGrid.innerHTML = ''; // Clear existing content
            
            data.classes.forEach(classData => {
                const classCard = document.createElement('div');
                classCard.className = 'class-card';
                classCard.innerHTML = `
                    <h3>${classData.class_name}</h3>
                    <p>Class Code: ${classData.class_code}</p>
                    <p>Students: ${classData.student_count}</p>
                    <div class="class-actions">
                        <button onclick="viewClass(${classData.id})">View Class</button>
                        <button onclick="editClass(${classData.id})">Edit</button>
                    </div>
                `;
                classesGrid.appendChild(classCard);
            });
        } else {
            console.error('Failed to load classes:', data.message);
        }
    } catch (error) {
        console.error('Error loading classes:', error);
    }
}

// Function to view a specific class
function viewClass(classId) {
    // Redirect to class details page
    window.location.href = `class-details.php?id=${classId}`;
}

// Function to edit a class
function editClass(classId) {
    // Redirect to class edit page
    window.location.href = `edit-class.php?id=${classId}`;
}

// Function to create a new class
function createClass() {
    // Redirect to create class page
    window.location.href = 'create-class.php';
}

// Function to update user profile in the sidebar
function updateUserProfile(teacher) {
    // Update user name
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach(element => {
        element.textContent = teacher.full_name;
    });
    
    // Update user ID
    const userIdElements = document.querySelectorAll('.user-id');
    userIdElements.forEach(element => {
        element.textContent = teacher.teacher_id;
    });
    
    // Update welcome message
    const welcomeMessage = document.querySelector('.dashboard-card .card-title');
    if (welcomeMessage) {
        welcomeMessage.textContent = `Welcome Back, ${teacher.full_name}!`;
    }
}

// Function to update dashboard content
function updateDashboardContent(data) {
    // This function will be expanded in the future to populate:
    // 1. Classes
    // 2. Students
    // 3. Assignments
    // 4. Grades
    // 5. Messages
    
    // For now, we're just showing empty states
}

// Function to handle navigation between sections
function navigateToSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.section-content');
    sections.forEach(section => {
        section.classList.add('hidden');
    });
    
    // Show the selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.remove('hidden');
    }
    
    // Update active nav link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${sectionId}`) {
            link.classList.add('active');
        }
    });
}

// Function to handle logout
function handleLogout() {
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'teacher-auth.html';
            } else {
                console.error('Logout failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
        });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load user data when the page loads
    loadUserData();
    loadClasses();
    
    // Set up navigation event listeners
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const sectionId = href.substring(1);
                navigateToSection(sectionId);
            } else if (href === 'teacher-auth.html') {
                e.preventDefault();
                handleLogout();
            }
        });
    });
    
    // Set up lesson form submission
    const addLessonForm = document.getElementById('addLessonForm');
    if (addLessonForm) {
        addLessonForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const title = document.getElementById('lessonTitle').value;
            const description = document.getElementById('lessonDescription').value;
            
            if (!title || !description) {
                showNotification('Please fill all required fields', 'error');
                return;
            }
            
            // Create FormData object
            const formData = new FormData();
            const classId = document.getElementById('classDetailsModal').dataset.classId;
            
            formData.append('class_id', classId);
            formData.append('title', title);
            formData.append('description', description);
            
            // Append files if any
            const files = document.getElementById('lessonFiles').files;
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitButton.disabled = true;
            
            // Send the request
            fetch('upload_lesson.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAddLessonModal();
                    showNotification(`Your lesson "${title}" was successfully uploaded!`, 'success');
                    // Reload class lessons
                    loadClassLessons(classId);
                } else {
                    console.error('Create lesson error:', data.debug);
                    showNotification(data.message || 'Failed to create lesson', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while creating the lesson', 'error');
            })
            .finally(() => {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                // Reset form
                addLessonForm.reset();
                document.getElementById('fileList').innerHTML = '';
            });
        });
    }
    
    // Set up notification button
    const notificationBtn = document.querySelector('.btn-primary');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // This will be implemented in the future
            alert('Notifications feature coming soon!');
        });
    }
});

// Function to manage class
function manageClass() {
    const classId = document.getElementById('classDetailsModal').dataset.classId;
    if (!classId) {
        alert('Error: Class ID not found');
        return;
    }
    document.getElementById('manageClassModal').style.display = 'flex';
    loadClassLessons(classId);
    
    // Set up tab switching
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panes
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            // Add active class to clicked button and corresponding pane
            this.classList.add('active');
            document.getElementById(`${this.dataset.tab}-tab`).classList.add('active');
            if (this.dataset.tab === 'lessons') {
                loadClassLessons(classId);
            }
        });
    });
}

// Handle assessment form submission
document.getElementById('addAssessmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate required fields
    const title = document.getElementById('assessmentTitle').value;
    const description = document.getElementById('assessmentDescription').value;
    const dueDate = document.getElementById('assessmentDueDate').value;
    const dueTime = document.getElementById('assessmentDueTime').value;
    const maxScore = document.getElementById('assessmentMaxScore').value;
    
    if (!title || !description || !dueDate || !dueTime) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    // Combine date and time into ISO format
    const combinedDueDate = dueDate + 'T' + dueTime;
    
    const formData = new FormData();
    const classId = document.getElementById('classDetailsModal').dataset.classId;
    
    formData.append('class_id', classId);
    formData.append('title', title);
    formData.append('description', description);
    formData.append('due_date', combinedDueDate);
    formData.append('max_score', maxScore);
    
    // Append files if any
    const files = document.getElementById('assessmentFiles').files;
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    submitButton.disabled = true;
    
    // Send the request
    fetch('upload_assessment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddAssessmentModal();
            showNotification('Assessment created successfully!', 'success');
        } else {
            console.error('Create assessment error:', data.debug);
            showNotification(data.message || 'Failed to create assessment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while creating the assessment', 'error');
    })
    .finally(() => {
        // Reset button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

// Function to update assessments grid
function updateAssessmentsGrid(assessments) {
    const grid = document.getElementById('assessmentsGrid');
    if (!grid) return;

    if (!assessments || assessments.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No assessments yet</p>
                <p>Create assessments to evaluate student progress</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = assessments.map(assessment => `
        <div class="lesson-card assessment-card" data-assessment-id="${assessment.id}">
            <div class="card-content" onclick="viewAssessmentDetails(${assessment.id})">
            <h3 class="lesson-title">${assessment.title}</h3>
            <p class="lesson-description">${assessment.description || 'No description'}</p>
                <div class="assessment-meta">
                    ${assessment.due_date ? `
                    <div class="due-date-info ${assessment.is_expired ? 'expired' : ''}">
                        <i class="fas fa-clock"></i>
                        <span>${assessment.is_expired ? 'Expired' : 'Due'}: ${formatDate(assessment.due_date)}</span>
                    </div>
                    ` : ''}
                    <div class="score-info">
                        <i class="fas fa-star"></i>
                        <span>Max Score: ${assessment.max_score || 100}</span>
                    </div>
                </div>
            <div class="lesson-files">
                    ${(assessment.files && assessment.files.length > 0) ? assessment.files.map(file => `
                    <div class="file-badge">
                        <i class="fas fa-file"></i>
                        <span>${file.file_name}</span>
                    </div>
                    `).join('') : '<div class="file-badge"><i class="fas fa-file-alt"></i><span>No files</span></div>'}
                </div>
            </div>
            <div class="lesson-actions">
                <button class="btn-delete" onclick="deleteAssessment(${assessment.id}, event)">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>
    `).join('');
}

// Function to delete assessment
function deleteAssessment(assessmentId) {
    if (confirm('Are you sure you want to delete this assessment? This action cannot be undone.')) {
        // Find the assessment card element
        const assessmentCard = document.querySelector(`[data-assessment-id="${assessmentId}"]`);
        if (!assessmentCard) return;

        // Add a fade-out animation
        assessmentCard.style.transition = 'opacity 0.3s ease-out';
        assessmentCard.style.opacity = '0';

        fetch(`delete_assessment.php?id=${assessmentId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card from DOM after animation
                setTimeout(() => {
                    assessmentCard.remove();
                    
                    // Check if there are no more assessments
                    const assessmentsGrid = document.getElementById('assessmentsGrid');
                    if (assessmentsGrid && assessmentsGrid.children.length === 0) {
                        assessmentsGrid.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p>No assessments yet</p>
                                <p>Create assessments to evaluate student progress</p>
                            </div>
                        `;
                    }
                }, 300); // Match the transition duration
            } else {
                // Restore the card if deletion failed
                assessmentCard.style.opacity = '1';
                alert('Error deleting assessment: ' + data.message);
            }
        })
        .catch(error => {
            // Restore the card if there was an error
            assessmentCard.style.opacity = '1';
            console.error('Error:', error);
            alert('An error occurred while deleting the assessment');
        });
    }
}

// Function to add lesson
function addLesson() {
    const classId = document.getElementById('classDetailsModal').dataset.classId;
    if (!classId) {
        alert('Error: Class ID not found');
        return;
    }
    
    // Show the add lesson modal
    document.getElementById('addLessonModal').style.display = 'flex';
}

// Function to close add lesson modal
function closeAddLessonModal() {
    document.getElementById('addLessonModal').style.display = 'none';
    // Reset form
    document.getElementById('addLessonForm').reset();
    // Clear file list
    const fileList = document.getElementById('fileList');
    if (fileList) {
        fileList.innerHTML = '';
    }
}

// Function to handle file selection for lesson upload
document.getElementById('lessonFiles').addEventListener('change', function() {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (this.files.length > 0) {
        Array.from(this.files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <i class="fas fa-file"></i>
                <span>${file.name}</span>
            `;
            fileList.appendChild(fileItem);
        });
    }
});

// Function to load class lessons
function loadClassLessons(classId) {
    const lessonsGrid = document.getElementById('lessonsGrid');
    if (!lessonsGrid) return;
    
    // Show loading state
    lessonsGrid.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading lessons...</p>
        </div>
    `;
    
    // Fetch lessons for the class
    fetch(`get_class_lessons.php?class_id=${classId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Lessons data:', data);
            
            if (data.success) {
                // Update lessons grid
                if (!data.lessons || data.lessons.length === 0) {
                    lessonsGrid.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <p>No lessons yet</p>
                            <p>Click "Add Lesson" to create your first lesson</p>
                        </div>
                        <div class="grid-actions">
                            <button class="btn-add" onclick="addLesson()">
                                <i class="fas fa-plus"></i> Add Lesson
                            </button>
                        </div>
                    `;
                } else {
                    lessonsGrid.innerHTML = `
                        <div class="grid-actions">
                            <button class="btn-add" onclick="addLesson()">
                                <i class="fas fa-plus"></i> Add Lesson
                            </button>
                        </div>
                        ${data.lessons.map(lesson => `
                            <div class="lesson-card" data-lesson-id="${lesson.id}">
                                <h3 class="lesson-title">${lesson.title}</h3>
                                <p class="lesson-description">${lesson.description || 'No description'}</p>
                                <div class="lesson-files">
                                    ${(lesson.files || []).map(file => `
                                        <div class="file-badge">
                                            <i class="fas fa-file"></i>
                                            <span>${file.file_name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                                <div class="lesson-actions">
                                    <button class="btn-view" onclick="viewLessonDetails(${lesson.id})">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>
                                    <button class="btn-delete" onclick="deleteLesson(${lesson.id})">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    `;
                }
            } else {
                lessonsGrid.innerHTML = `
                    <div class="empty-state error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading lessons</p>
                        <p>${data.message || 'Unknown error'}</p>
                    </div>
                `;
                console.error('Failed to load lessons:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading lessons:', error);
            lessonsGrid.innerHTML = `
                <div class="empty-state error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading lessons</p>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Function to show notifications
if (typeof showNotification !== 'function') {
    window.showNotification = function(message, type = 'success') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
        
        // Set message and type
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.style.display = 'block';
        
        // Set styles to make notification more prominent
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.left = '50%';
        notification.style.transform = 'translateX(-50%)';
        notification.style.padding = '15px 30px';
        notification.style.borderRadius = '6px';
        notification.style.color = 'white';
        notification.style.fontWeight = '600';
        notification.style.zIndex = '9999';
        notification.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.15)';
        notification.style.fontSize = '16px';
        
        if (type === 'success') {
            notification.style.backgroundColor = '#2ecc71';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#e74c3c';
        } else if (type === 'warning') {
            notification.style.backgroundColor = '#f39c12';
        } else {
            notification.style.backgroundColor = '#3498db';
        }
        
        // Animation
        notification.style.animation = 'fadeIn 0.4s';
        
        // Remove after timeout
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.4s';
            setTimeout(() => {
                notification.remove();
            }, 400);
        }, 4000);
    };
}

// Function to delete a lesson
function deleteLesson(lessonId) {
    if (confirm('Are you sure you want to delete this lesson? This action cannot be undone.')) {
        // Find the lesson card element
        const lessonCard = document.querySelector(`[data-lesson-id="${lessonId}"]`);
        if (!lessonCard) return;
        
        // Add a fade-out animation
        lessonCard.style.transition = 'opacity 0.3s ease-out';
        lessonCard.style.opacity = '0';
        
        fetch(`delete_lesson.php?id=${lessonId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card from DOM after animation
                setTimeout(() => {
                    lessonCard.remove();
                    
                    // Check if there are no more lessons
                    const lessonsGrid = document.getElementById('lessonsGrid');
                    if (lessonsGrid && lessonsGrid.querySelectorAll('.lesson-card').length === 0) {
                        lessonsGrid.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>No lessons yet</p>
                                <p>Click "Add Lesson" to create your first lesson</p>
                            </div>
                            <div class="grid-actions">
                                <button class="btn-add" onclick="addLesson()">
                                    <i class="fas fa-plus"></i> Add Lesson
                                </button>
                            </div>
                        `;
                    }
                    
                    showNotification('Lesson deleted successfully', 'success');
                }, 300); // Match the transition duration
            } else {
                // Restore the card if deletion failed
                lessonCard.style.opacity = '1';
                showNotification('Error deleting lesson: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            // Restore the card if there was an error
            lessonCard.style.opacity = '1';
            console.error('Error:', error);
            showNotification('An error occurred while deleting the lesson', 'error');
        });
    }
}

// Function to view class details
function viewClassDetails(classId) {
    // Show the modal
    showModal('classDetailsModal');
    
    // Store the class ID in the modal for later use
    document.getElementById('classDetailsModal').dataset.classId = classId;
    
    // Fetch class details from server
    fetch(`get_class_details.php?id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const class_ = data.class;
                
                // Update modal content
                document.getElementById('classDetailsTitle').textContent = class_.class_name;
                document.getElementById('classDetailsCode').textContent = `Class Code: ${class_.class_code}`;
                document.getElementById('classCreatedDate').textContent = `Created: ${new Date(class_.created_at).toLocaleDateString()}`;
                document.getElementById('classStudentCount').innerHTML = `<span class="student-count">Students: ${class_.student_count}</span>`;
                
                // Update student list
                const studentList = document.getElementById('classStudentList');
                if (class_.students.length > 0) {
                    studentList.innerHTML = class_.students.map(student => `
                        <div class="student-item">
                            <div class="student-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div>${student.name}</div>
                                <div style="font-size: 12px; color: #7f8c8d;">${student.email}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    studentList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <p>No students enrolled yet</p>
                        </div>
                    `;
                }
            } else {
                showNotification('Error loading class details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while loading class details', 'error');
        });
}

// Function to update the classes grid
function updateClassesGrid(classes) {
    const grid = document.querySelector('.classes-grid');
    if (!grid) return;

    if (classes.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-chalkboard"></i>
                <p>No classes created yet</p>
                <p>Click "Add New Class" to create your first class</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = classes.map(class_ => `
        <div class="class-card" onclick="viewClassDetails(${class_.id})">
            <div class="class-header">
                <h3 class="class-title">${class_.class_name}</h3>
                <span class="class-code">${class_.class_code}</span>
            </div>
            <div class="class-info">
                <div class="class-info-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>${class_.student_count || 0} students</span>
                </div>
                <div class="class-info-item">
                    <i class="fas fa-calendar"></i>
                    <span>Created ${new Date(class_.created_at).toLocaleDateString()}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Function to view assessment details
function viewAssessmentDetails(assessmentId) {
    // Store the assessment ID for delete functionality
    document.getElementById('assessmentDetailsModal').dataset.assessmentId = assessmentId;
    
    // Show the modal - keep other modals open (like the manage class modal)
    showModal('assessmentDetailsModal', true);
    
    // Show loading state
    document.getElementById('assessmentDetailsTitle').textContent = 'Loading...';
    document.getElementById('assessmentDetailsDescription').textContent = 'Loading assessment details...';
    document.getElementById('assessmentDetailsFiles').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading files...</p>
        </div>
    `;
    
    // Fetch assessment details from server
    fetch(`get_assessment.php?id=${assessmentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const assessment = data.data;
                
                // Update modal content
                document.getElementById('assessmentDetailsTitle').textContent = assessment.title || 'Untitled Assessment';
                document.getElementById('assessmentDetailsDate').textContent = `Created: ${formatDate(assessment.created_at)}`;
                document.getElementById('assessmentDetailsDescription').textContent = assessment.description || 'No description provided.';
                
                // Update due date
                const dueDateDiv = document.getElementById('assessmentDetailsDueDate');
                if (assessment.due_date) {
                    const isExpired = new Date(assessment.due_date) < new Date();
                    dueDateDiv.className = `due-date-info ${isExpired ? 'expired' : ''}`;
                    dueDateDiv.innerHTML = `
                        <i class="fas fa-clock"></i>
                        <span>${isExpired ? 'Expired' : 'Due'}: ${formatDate(assessment.due_date)}</span>
                    `;
                } else {
                    dueDateDiv.className = 'due-date-info';
                    dueDateDiv.innerHTML = `
                        <i class="fas fa-clock"></i>
                        <span>No due date set</span>
                    `;
                }
                
                // Update max score information
                const scoreDiv = document.getElementById('assessmentDetailsMaxScore');
                if (scoreDiv) {
                    scoreDiv.innerHTML = `
                        <i class="fas fa-star"></i>
                        <span>Maximum Score: ${assessment.max_score || 100}</span>
                    `;
                }
                
                // Update file list
                const fileList = document.getElementById('assessmentDetailsFiles');
                if (assessment.files && assessment.files.length > 0) {
                    fileList.innerHTML = assessment.files.map(file => `
                        <div class="detail-file-item">
                            <i class="${getFileIcon(file.file_type)}"></i>
                            <div class="file-info">
                                <div class="file-name">${file.file_name}</div>
                                <div class="file-meta">
                                    ${formatFileSize(file.file_size)} • ${getFileTypeLabel(file.file_type)}
                                </div>
                            </div>
                            <a href="${file.file_path}" class="file-download" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    `).join('');
                } else {
                    fileList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-file"></i>
                            <p>No files attached to this assessment</p>
                        </div>
                    `;
                }
            } else {
                showNotification('Error loading assessment details: ' + (data.message || 'Unknown error'), 'error');
                hideModal('assessmentDetailsModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(`Error loading assessment details: ${error.message}`, 'error');
            hideModal('assessmentDetailsModal');
        });
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return 'Not set';
    
    try {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString(undefined, options);
    } catch(e) {
        console.error('Error formatting date:', e);
        return dateString;
    }
}

// Helper function to get file icon based on file type
function getFileIcon(fileType) {
    if (!fileType) return 'fas fa-file';
    
    if (fileType.includes('pdf')) {
        return 'fas fa-file-pdf';
    } else if (fileType.includes('word') || fileType.includes('doc')) {
        return 'fas fa-file-word';
    } else if (fileType.includes('excel') || fileType.includes('spreadsheet') || fileType.includes('xlsx') || fileType.includes('xls')) {
        return 'fas fa-file-excel';
    } else if (fileType.includes('powerpoint') || fileType.includes('presentation') || fileType.includes('ppt')) {
        return 'fas fa-file-powerpoint';
    } else if (fileType.includes('image') || fileType.includes('jpg') || fileType.includes('jpeg') || fileType.includes('png') || fileType.includes('gif')) {
        return 'fas fa-file-image';
    } else if (fileType.includes('audio') || fileType.includes('mp3') || fileType.includes('wav')) {
        return 'fas fa-file-audio';
    } else if (fileType.includes('video') || fileType.includes('mp4') || fileType.includes('mov')) {
        return 'fas fa-file-video';
    } else if (fileType.includes('zip') || fileType.includes('archive') || fileType.includes('rar') || fileType.includes('7z')) {
        return 'fas fa-file-archive';
    } else if (fileType.includes('code') || fileType.includes('text') || fileType.includes('html') || fileType.includes('js') || fileType.includes('css')) {
        return 'fas fa-file-code';
    } else {
        return 'fas fa-file';
    }
}

// Helper function to format file size
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
}

// Helper function to get file type label
function getFileTypeLabel(fileType) {
    if (!fileType) return 'Unknown';
    
    if (fileType.includes('pdf')) {
        return 'PDF Document';
    } else if (fileType.includes('word') || fileType.includes('doc')) {
        return 'Word Document';
    } else if (fileType.includes('excel') || fileType.includes('spreadsheet') || fileType.includes('xlsx') || fileType.includes('xls')) {
        return 'Excel Spreadsheet';
    } else if (fileType.includes('powerpoint') || fileType.includes('presentation') || fileType.includes('ppt')) {
        return 'PowerPoint Presentation';
    } else if (fileType.includes('image')) {
        return 'Image';
    } else if (fileType.includes('audio')) {
        return 'Audio File';
    } else if (fileType.includes('video')) {
        return 'Video File';
    } else if (fileType.includes('zip') || fileType.includes('archive') || fileType.includes('rar') || fileType.includes('7z')) {
        return 'Archive';
    } else if (fileType.includes('text')) {
        return 'Text File';
    } else {
        // Extract extension from mime type if possible
        const parts = fileType.split('/');
        if (parts.length === 2) {
            return parts[1].toUpperCase() + ' File';
        }
        return fileType;
    }
}

// Function to load class assessments
function loadClassAssessments(classId) {
    fetch(`list_assessments.php?class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAssessmentsGrid(data.data);
            } else {
                console.error('Error loading assessments:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching assessments:', error);
        });
}

// Function to view submissions for an assessment
function viewSubmissionsForAssessment() {
    console.log("viewSubmissionsForAssessment called");
    
    // Get assessment ID from the current modal
    const assessmentDetailsModal = document.getElementById('assessmentDetailsModal');
    if (!assessmentDetailsModal) {
        console.error("Assessment details modal not found");
        window.showNotification('Error: Assessment details modal not found', 'error');
        return;
    }
    
    const assessmentId = assessmentDetailsModal.dataset.assessmentId;
    const assessmentTitle = document.getElementById('assessmentDetailsTitle')?.textContent || 'Unknown Assessment';
    
    console.log("Assessment ID:", assessmentId);
    console.log("Assessment Title:", assessmentTitle);
    
    if (!assessmentId) {
        console.error("Assessment ID not found in dataset");
        window.showNotification('Error: Assessment ID not found', 'error');
        return;
    }
    
    // Show the submissions modal and ensure it's on top of everything
    const submissionsModal = document.getElementById('submissionsModal');
    if (!submissionsModal) {
        console.error("Submissions modal not found in DOM");
        window.showNotification('Error: Submissions modal not found', 'error');
        return;
    }
    console.log("Submissions Modal Element:", submissionsModal);
    
    // First close any existing active modals that might be in the way
    document.querySelectorAll('.modal.active').forEach(modal => {
        if (modal.id !== 'assessmentDetailsModal') {
            console.log("Closing modal:", modal.id);
            window.hideModal(modal.id);
        }
    });
    
    // Create backdrop overlay for emphasis
    let overlay = document.querySelector('.submissions-overlay');
    if (!overlay) {
        console.log("Creating new overlay");
        overlay = document.createElement('div');
        overlay.className = 'submissions-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.7)';
        overlay.style.backdropFilter = 'blur(3px)';
        overlay.style.zIndex = '99999';
        document.body.appendChild(overlay);
        
        // Close when clicking overlay
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeSubmissionsModal();
            }
        });
    }
    overlay.style.display = 'block';
    
    try {
        // Force modal to be visible
        submissionsModal.style.display = 'flex';
        submissionsModal.classList.add('active');
        submissionsModal.style.zIndex = '100000';
        
        // Call the show modal function afterwards
        console.log("Showing submissions modal");
        window.showModal('submissionsModal', true);
    } catch (error) {
        console.error("Error showing modal:", error);
    }
    
    // Set the assessment title in the modal
    const titleElement = document.getElementById('submissionsAssessmentTitle');
    console.log("Title Element:", titleElement);
    if (titleElement) {
        titleElement.textContent = `Assessment: ${assessmentTitle}`;
    } else {
        console.error("Title element not found");
    }
    
    // Show loading state
    const contentElement = document.getElementById('submissionsContent');
    console.log("Content Element:", contentElement);
    if (contentElement) {
        contentElement.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading submissions...</p>
            </div>
        `;
    } else {
        console.error("Content element not found");
    }
    
    // Fetch submissions from the server
    console.log("Fetching from:", `get_assessment.php?id=${assessmentId}`);
    fetch(`get_assessment.php?id=${assessmentId}`)
        .then(response => {
            console.log("Response received:", response);
            return response.json();
        })
        .then(data => {
            console.log("Data received:", data);
            if (data.success) {
                const assessment = data.data;
                
                // Check if there are any submissions
                if (!assessment.submissions || assessment.submissions.length === 0) {
                    if (contentElement) {
                        contentElement.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <p>No submissions yet</p>
                            </div>
                        `;
                    }
                    return;
                }
                
                // Sort submissions by date (newest first)
                const submissions = assessment.submissions.sort((a, b) => {
                    return new Date(b.submission_date) - new Date(a.submission_date);
                });
                
                // Build the submissions table
                let tableHtml = `
                    <div style="overflow-x: auto; margin-top: 20px;">
                        <table style="width: 100%; border-collapse: collapse; border-spacing: 0;">
                            <thead>
                                <tr style="background-color: #f5f7fa;">
                                    <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Student</th>
                                    <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Submitted</th>
                                    <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Score</th>
                                    <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Files</th>
                                    <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                submissions.forEach(submission => {
                    tableHtml += `
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 12px 15px;">
                                <div style="font-weight: 600;">${submission.student_name || 'Unknown Student'}</div>
                                <div style="font-size: 12px; color: #7f8c8d;">${submission.student_email || ''}</div>
                            </td>
                            <td style="padding: 12px 15px;">${formatDate(submission.submission_date)}</td>
                            <td style="padding: 12px 15px;">
                                ${submission.score !== null 
                                    ? `<span style="font-weight: 600; color: #27ae60;">${submission.score}/${assessment.max_score}</span>` 
                                    : '<span style="color: #7f8c8d;">Not graded</span>'}
                            </td>
                            <td style="padding: 12px 15px;">
                                <a href="get_submission_files.php?submission_id=${submission.id}" class="btn-secondary" style="display: inline-block; padding: 6px 12px; font-size: 12px; text-decoration: none;">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                            <td style="padding: 12px 15px;">
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="gradeSubmission(${submission.id}, ${assessment.max_score})">
                                    <i class="fas fa-check-circle"></i> Grade
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                tableHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                if (contentElement) {
                    contentElement.innerHTML = tableHtml;
                }
            } else {
                if (contentElement) {
                    contentElement.innerHTML = `
                        <div class="empty-state error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error loading submissions</p>
                            <p>${data.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
                console.error('Error loading submissions:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading submissions:', error);
            if (contentElement) {
                contentElement.innerHTML = `
                    <div class="empty-state error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading submissions</p>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        });
}

// Function to close the submissions modal
function closeSubmissionsModal() {
    window.hideModal('submissionsModal');
    
    // Hide the overlay
    const overlay = document.querySelector('.submissions-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Function to grade a submission
function gradeSubmission(submissionId, maxScore) {
    // Prompt for the score
    const score = prompt(`Enter score (0-${maxScore}):`, '');
    
    // Validate score
    if (score === null) {
        // User cancelled
        return;
    }
    
    const numScore = parseInt(score, 10);
    if (isNaN(numScore) || numScore < 0 || numScore > maxScore) {
        showNotification(`Please enter a valid score between 0 and ${maxScore}`, 'error');
        return;
    }
    
    // Optional feedback
    const feedback = prompt('Enter feedback (optional):', '');
    
    // Send grade to server
    fetch('grade_submission.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            submission_id: submissionId,
            score: numScore,
            feedback: feedback || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Submission graded successfully', 'success');
            // Refresh submissions view
            viewSubmissionsForAssessment();
        } else {
            showNotification(data.message || 'Error grading submission', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while grading the submission', 'error');
    });
} 