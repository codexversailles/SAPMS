// Function to load user data from the server
function loadUserData() {
    fetch('get-parent-data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUserProfile(data.parent);
                updateDashboardContent(data);
            } else {
                console.error('Error loading user data:', data.message);
                // Redirect to login if not logged in
                if (data.message === 'Not logged in') {
                    window.location.href = 'parent-auth.html';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });
}

// Function to update user profile in the dashboard
function updateUserProfile(parent) {
    // Update user name
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach(element => {
        element.textContent = parent.parent_full_name || parent.full_name;
    });
}

// Function to update dashboard content
function updateDashboardContent(data) {
    // Update children section
    const childrenContainer = document.getElementById('children-container');
    if (childrenContainer) {
        // Clear existing content
        childrenContainer.innerHTML = '';
        
        // First check if we have children data and display all children
        if (data.children && data.children.length > 0) {
            // Add each child to the display
            data.children.forEach(child => {
                const childCard = document.createElement('div');
                childCard.className = 'child-card';
                childCard.id = `child-${child.id}`;
                childCard.dataset.studentId = child.student_id;
                childCard.innerHTML = `
                    <div class="child-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="child-info">
                        <div class="child-name">${child.full_name}</div>
                        <div class="child-id">ID: ${child.student_id}</div>
                        <div class="child-relationship">${child.relationship}</div>
                    </div>
                `;
                
                childCard.addEventListener('click', function() {
                    loadChildDetails(child.student_id, child.full_name);
                });
                
                childrenContainer.appendChild(childCard);
            });
        } 
        // Fallback for single child in parent object
        else if (data.parent && data.parent.student_id) {
            // Create child card
            const childCard = document.createElement('div');
            childCard.className = 'child-card';
            childCard.innerHTML = `
                <div class="child-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="child-info">
                <div class="child-name">${data.parent.student_full_name}</div>
                <div class="child-id">ID: ${data.parent.student_id}</div>
                <div class="child-relationship">${data.parent.relationship}</div>
                </div>
            `;
            
            childCard.addEventListener('click', function() {
                loadChildDetails(data.parent.student_id, data.parent.student_full_name);
            });
            
            childrenContainer.appendChild(childCard);
        } else {
            // Show empty state if no children
            childrenContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-child"></i>
                    <p>No children registered yet</p>
                    <p>Your children will appear here</p>
                </div>
            `;
        }
    }
}

// Global variables to store original data for filtering
let originalAssessments = [];
let originalLessons = [];
let originalAnnouncements = [];
let availableClasses = [];
let availableTeachers = {};

// Function to load child details
function loadChildDetails(studentId, childName) {
    // Show loading state
    document.getElementById('detail-child-name').textContent = childName;
    document.getElementById('detail-child-id').textContent = `ID: ${studentId}`;
    document.getElementById('child-detail-section').style.display = 'block';
    
    // Clear existing data and show loading indicators
    document.getElementById('classes-container').innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading classes...</div>';
    document.getElementById('assessments-container').innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner"></i> Loading assessments...</td></tr>';
    document.getElementById('lessons-container').innerHTML = '<tr><td colspan="4" class="loading"><i class="fas fa-spinner"></i> Loading lessons...</td></tr>';
    document.getElementById('announcements-container').innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner"></i> Loading announcements...</td></tr>';
    
    // Reset filter dropdowns
    document.getElementById('assessment-class-filter').innerHTML = '<option value="all">All Subjects</option>';
    document.getElementById('assessment-teacher-filter').innerHTML = '<option value="all">All Teachers</option>';
    document.getElementById('lesson-class-filter').innerHTML = '<option value="all">All Subjects</option>';
    document.getElementById('lesson-teacher-filter').innerHTML = '<option value="all">All Teachers</option>';
    document.getElementById('announcement-class-filter').innerHTML = '<option value="all">All Subjects</option>';
    
    // Reset global variables
    originalAssessments = [];
    originalLessons = [];
    originalAnnouncements = [];
    availableClasses = [];
    availableTeachers = {};
    
    // Highlight the selected child card
    document.querySelectorAll('.child-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelectorAll(`.child-card[data-student-id="${studentId}"]`).forEach(card => {
        card.classList.add('active');
    });
    
    // Scroll to the details section
    document.getElementById('child-detail-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Fetch detailed data for this child
    fetch(`get_child_details.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            console.log('API Response for child details:', data); // Debug: Log full response
            
            if (data.success) {
                // Process classes first to build available classes and teachers
                if (data.classes && data.classes.length > 0) {
                    processClassesForFilters(data.classes);
                }
                
                displayChildClasses(data.classes);
                
                // Store original data for filtering
                if (data.assessments) {
                    originalAssessments = data.assessments;
                }
                
                if (data.lessons) {
                    originalLessons = data.lessons;
                }
                
                // Display data
                displayChildAssessments(data.assessments);
                displayChildLessons(data.lessons);
                
                // Use announcements data from the API if available
                if (data.announcements) {
                    console.log('Announcements data received:', data.announcements);
                    console.log('Announcements count:', data.announcements.length);
                    originalAnnouncements = data.announcements;
                    displayChildAnnouncements(data.announcements);
                } else {
                    console.log('No announcements data in response');
                    originalAnnouncements = [];
                    // Fallback to empty state if no announcements data
                    document.getElementById('announcements-container').innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-bullhorn"></i>
                                    <p>No Announcements Found</p>
                                    <p>There are no announcements for this student's classes yet.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                
                // Set up filter event listeners
                setupFilterEventListeners();
                
            } else {
                console.error('Error loading child details:', data.message);
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while loading child details', 'error');
        });
}

// Function to process classes for filter dropdowns
function processClassesForFilters(classes) {
    availableClasses = [];
    availableTeachers = {};
    
    // Extract unique classes and teachers
    classes.forEach(classInfo => {
        // Add class to available classes if not already there
        if (!availableClasses.some(c => c.id === classInfo.class_id)) {
            availableClasses.push({
                id: classInfo.class_id,
                name: classInfo.class_name
            });
        }
        
        // Add teacher to available teachers if not already there
        if (!availableTeachers[classInfo.teacher_id]) {
            availableTeachers[classInfo.teacher_id] = {
                id: classInfo.teacher_id,
                name: classInfo.teacher_name
            };
        }
    });
    
    // Populate class filter dropdowns
    populateClassDropdowns();
    
    // Populate teacher filter dropdowns
    populateTeacherDropdowns();
}

// Function to populate class dropdowns
function populateClassDropdowns() {
    const assessmentClassFilter = document.getElementById('assessment-class-filter');
    const lessonClassFilter = document.getElementById('lesson-class-filter');
    const announcementClassFilter = document.getElementById('announcement-class-filter');
    
    // Clear existing options except "All Subjects"
    assessmentClassFilter.innerHTML = '<option value="all">All Subjects</option>';
    lessonClassFilter.innerHTML = '<option value="all">All Subjects</option>';
    announcementClassFilter.innerHTML = '<option value="all">All Subjects</option>';
    
    // Add classes to dropdowns
    availableClasses.forEach(classInfo => {
        const option = document.createElement('option');
        option.value = classInfo.id;
        option.textContent = classInfo.name;
        
        assessmentClassFilter.appendChild(option.cloneNode(true));
        lessonClassFilter.appendChild(option.cloneNode(true));
        announcementClassFilter.appendChild(option.cloneNode(true));
    });
}

// Function to populate teacher dropdowns
function populateTeacherDropdowns() {
    const assessmentTeacherFilter = document.getElementById('assessment-teacher-filter');
    const lessonTeacherFilter = document.getElementById('lesson-teacher-filter');
    
    // Clear existing options except "All Teachers"
    assessmentTeacherFilter.innerHTML = '<option value="all">All Teachers</option>';
    lessonTeacherFilter.innerHTML = '<option value="all">All Teachers</option>';
    
    // Add teachers to dropdowns
    Object.values(availableTeachers).forEach(teacher => {
        const option = document.createElement('option');
        option.value = teacher.id;
        option.textContent = teacher.name;
        
        assessmentTeacherFilter.appendChild(option.cloneNode(true));
        lessonTeacherFilter.appendChild(option);
    });
}

// Function to set up filter event listeners
function setupFilterEventListeners() {
    // Assessment filters
    document.getElementById('assessment-class-filter').addEventListener('change', filterAssessments);
    document.getElementById('assessment-teacher-filter').addEventListener('change', filterAssessments);
    document.getElementById('assessment-sort-order').addEventListener('change', filterAssessments);
    
    // Lesson filters
    document.getElementById('lesson-class-filter').addEventListener('change', filterLessons);
    document.getElementById('lesson-teacher-filter').addEventListener('change', filterLessons);
    document.getElementById('lesson-sort-order').addEventListener('change', filterLessons);
    
    // Announcement filters
    document.getElementById('announcement-class-filter').addEventListener('change', filterAnnouncements);
    document.getElementById('announcement-visibility-filter').addEventListener('change', filterAnnouncements);
    document.getElementById('announcement-sort-order').addEventListener('change', filterAnnouncements);
}

// Function to filter and display assessments
function filterAssessments() {
    const classFilter = document.getElementById('assessment-class-filter').value;
    const teacherFilter = document.getElementById('assessment-teacher-filter').value;
    const sortOrder = document.getElementById('assessment-sort-order').value;
    
    console.log('Filtering assessments:', { classFilter, teacherFilter, sortOrder });
    
    // Create a copy of the original data for filtering
    let filteredAssessments = [...originalAssessments];
    
    // Apply class filter if not "all"
    if (classFilter !== 'all') {
        filteredAssessments = filteredAssessments.filter(assessment => 
            assessment.class_id === classFilter || assessment.class_id === parseInt(classFilter)
        );
    }
    
    // Apply teacher filter if not "all"
    if (teacherFilter !== 'all') {
        // For teacher filtering, we'll need to join with the classes data
        filteredAssessments = filteredAssessments.filter(assessment => {
            // Find the class info for this assessment
            const classInfo = availableClasses.find(c => 
                c.id === assessment.class_id || c.id === parseInt(assessment.class_id)
            );
            // Find the corresponding teacher info
            const teacher = availableTeachers[classInfo?.teacher_id];
            return teacher && (teacher.id === teacherFilter || teacher.id === parseInt(teacherFilter));
        });
    }
    
    // Apply sorting
    switch (sortOrder) {
        case 'due-asc':
            filteredAssessments.sort((a, b) => new Date(a.due_date || 0) - new Date(b.due_date || 0));
            break;
        case 'due-desc':
            filteredAssessments.sort((a, b) => new Date(b.due_date || 0) - new Date(a.due_date || 0));
            break;
        case 'score-asc':
            filteredAssessments.sort((a, b) => (a.score || 0) - (b.score || 0));
            break;
        case 'score-desc':
            filteredAssessments.sort((a, b) => (b.score || 0) - (a.score || 0));
            break;
    }
    
    // Display the filtered and sorted assessments
    displayChildAssessments(filteredAssessments, false);
}

// Function to filter and display lessons
function filterLessons() {
    const classFilter = document.getElementById('lesson-class-filter').value;
    const teacherFilter = document.getElementById('lesson-teacher-filter').value;
    const sortOrder = document.getElementById('lesson-sort-order').value;
    
    console.log('Filtering lessons:', { classFilter, teacherFilter, sortOrder });
    
    // Create a copy of the original data for filtering
    let filteredLessons = [...originalLessons];
    
    // Apply class filter if not "all"
    if (classFilter !== 'all') {
        filteredLessons = filteredLessons.filter(lesson => 
            lesson.class_id === classFilter || lesson.class_id === parseInt(classFilter)
        );
    }
    
    // Apply teacher filter if not "all"
    if (teacherFilter !== 'all') {
        // For teacher filtering, we'll need to join with the classes data
        filteredLessons = filteredLessons.filter(lesson => {
            // Find the class info for this lesson
            const classInfo = availableClasses.find(c => 
                c.id === lesson.class_id || c.id === parseInt(lesson.class_id)
            );
            // Find the corresponding teacher info
            const teacher = availableTeachers[classInfo?.teacher_id];
            return teacher && (teacher.id === teacherFilter || teacher.id === parseInt(teacherFilter));
        });
    }
    
    // Apply sorting
    switch (sortOrder) {
        case 'date-asc':
            filteredLessons.sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
            break;
        case 'date-desc':
            filteredLessons.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
            break;
        case 'title-asc':
            filteredLessons.sort((a, b) => a.title.localeCompare(b.title));
            break;
        case 'title-desc':
            filteredLessons.sort((a, b) => b.title.localeCompare(a.title));
            break;
    }
    
    // Display the filtered and sorted lessons
    displayChildLessons(filteredLessons, false);
}

// Function to filter and display announcements
function filterAnnouncements() {
    const classFilter = document.getElementById('announcement-class-filter').value;
    const visibilityFilter = document.getElementById('announcement-visibility-filter').value;
    const sortOrder = document.getElementById('announcement-sort-order').value;
    
    console.log('Filtering announcements:', { classFilter, visibilityFilter, sortOrder });
    
    // Create a copy of the original data for filtering
    let filteredAnnouncements = [...originalAnnouncements];
    
    // Apply class filter if not "all"
    if (classFilter !== 'all') {
        filteredAnnouncements = filteredAnnouncements.filter(announcement => 
            announcement.class_id === classFilter || announcement.class_id === parseInt(classFilter)
        );
    }
    
    // Apply visibility filter if not "all"
    if (visibilityFilter !== 'all') {
        filteredAnnouncements = filteredAnnouncements.filter(announcement => 
            announcement.visibility === visibilityFilter
        );
    }
    
    // Priority mapping for sorting
    const priorityValues = {
        'normal': 0,
        'important': 1,
        'urgent': 2
    };
    
    // Apply sorting
    switch (sortOrder) {
        case 'date-asc':
            filteredAnnouncements.sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
            break;
        case 'date-desc':
            filteredAnnouncements.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
            break;
        case 'priority-asc':
            filteredAnnouncements.sort((a, b) => 
                (priorityValues[a.priority] || 0) - (priorityValues[b.priority] || 0)
            );
            break;
        case 'priority-desc':
            filteredAnnouncements.sort((a, b) => 
                (priorityValues[b.priority] || 0) - (priorityValues[a.priority] || 0)
            );
            break;
    }
    
    // Display the filtered and sorted announcements
    displayChildAnnouncements(filteredAnnouncements, false);
}

// Function to display classes
function displayChildClasses(classes) {
    const container = document.getElementById('classes-container');
    
    if (classes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-chalkboard"></i>
                <p>No Classes Found</p>
                <p>This student is not enrolled in any classes yet.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    classes.forEach(classInfo => {
        console.log('Class info:', classInfo); // Debug: Log the class info
        
        const classCard = document.createElement('div');
        classCard.className = 'class-card';
        classCard.innerHTML = `
            <h4>${classInfo.class_name}</h4>
            <div class="class-header-actions">
                <span class="class-code">Code: ${classInfo.class_code}</span>
                <button class="message-teacher-btn" 
                        data-teacher-id="${classInfo.teacher_id}" 
                        data-class-id="${classInfo.class_id}"
                        data-teacher-name="${classInfo.teacher_name}"
                        data-class-name="${classInfo.class_name}">
                    <i class="fas fa-comment"></i> 
                    Send a message to teacher
                </button>
            </div>
            <div class="class-info">
                <div class="info-row">
                    <span class="info-label">Teacher:</span>
                    <span class="info-value">${classInfo.teacher_name}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${classInfo.teacher_email || 'Not available'}</span>
                </div>
            </div>
        `;
        container.appendChild(classCard);
        
        // Add event listener to the message teacher button
        const messageButton = classCard.querySelector('.message-teacher-btn');
        messageButton.addEventListener('click', function() {
            const teacherId = this.dataset.teacherId;
            const classId = this.dataset.classId;
            const teacherName = this.dataset.teacherName;
            const className = this.dataset.className;
            
            console.log('Opening chat with:', {teacherId, classId, teacherName, className}); // Debug: Log the chat parameters
            
            openTeacherChat(teacherId, classId, teacherName, className);
        });
    });
}

// Function to display assessments
function displayChildAssessments(assessments, isInitialLoad = true) {
    const container = document.getElementById('assessments-container');
    
    if (!assessments || assessments.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <p>No Assessments Found</p>
                        <p>There are no assessments assigned to this student yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    container.innerHTML = '';
    assessments.forEach(assessment => {
        // Calculate score class based on percentage
        let scoreClass = 'no-score';
        let scoreDisplay = 'Not submitted';
        
        if (assessment.score !== null) {
            const scorePercentage = (assessment.score / assessment.max_score) * 100;
            scoreDisplay = `${assessment.score}/${assessment.max_score}`;
            
            if (scorePercentage >= 80) {
                scoreClass = 'good-score';
            } else if (scorePercentage >= 60) {
                scoreClass = 'average-score';
            } else {
                scoreClass = 'poor-score';
            }
    }
    
        // Format due date
        const dueDate = assessment.due_date ? new Date(assessment.due_date).toLocaleDateString() : 'No deadline';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${assessment.class_name}</td>
            <td class="truncate" title="${assessment.title}">${assessment.title}</td>
            <td class="date">${dueDate}</td>
            <td><span class="score ${scoreClass}">${scoreDisplay}</span></td>
            <td class="truncate" title="${assessment.teacher_feedback || 'No feedback yet'}">${assessment.teacher_feedback || 'No feedback yet'}</td>
        `;
        
        container.appendChild(row);
    });
}

// Function to display lessons
function displayChildLessons(lessons, isInitialLoad = true) {
    const container = document.getElementById('lessons-container');
    
    if (!lessons || lessons.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <p>No Lessons Found</p>
                        <p>There are no lessons available for this student yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    container.innerHTML = '';
    lessons.forEach(lesson => {
        // Format date
        const dateAdded = lesson.created_at ? new Date(lesson.created_at).toLocaleDateString() : 'Unknown date';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${lesson.class_name}</td>
            <td class="truncate" title="${lesson.title}">${lesson.title}</td>
            <td class="date">${dateAdded}</td>
            <td class="truncate" title="${lesson.description}">${lesson.description}</td>
        `;
        container.appendChild(row);
    });
}

// Function to display announcements
function displayChildAnnouncements(announcements, isInitialLoad = true) {
    console.log('displayChildAnnouncements called with:', announcements); // Debug
    const container = document.getElementById('announcements-container');
    
    if (!announcements || announcements.length === 0) {
        console.log('No announcements to display, showing empty state'); // Debug
        container.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>No Announcements Found</p>
                        <p>There are no announcements for this student's classes yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    console.log('Processing announcements, count:', announcements.length); // Debug
    
    try {
        container.innerHTML = '';
        announcements.forEach((announcement, index) => {
            console.log(`Processing announcement ${index}:`, announcement); // Debug
            
            // Format date
            const datePosted = announcement.created_at ? new Date(announcement.created_at).toLocaleDateString() : 'Unknown date';
            
            // Determine priority styling and icon
            let priorityClass = '';
            let priorityDisplay = 'Normal';
            
            switch(announcement.priority) {
                case 'urgent':
                    priorityClass = 'priority-urgent';
                    priorityDisplay = 'Urgent';
                    break;
                case 'important':
                    priorityClass = 'priority-important';
                    priorityDisplay = 'Important';
                    break;
            }
            
            // Add parent-specific indicator for parent announcements
            const isParentOnly = announcement.visibility === 'parents';
            const visibilityIcon = isParentOnly ? 
                '<i class="fas fa-user-friends" title="Parent announcement" style="margin-left: 5px; color: #3498db;"></i>' : '';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${announcement.class_name}</td>
                <td class="truncate" title="${announcement.title}">
                    ${announcement.title} ${visibilityIcon}
                </td>
                <td class="date">${datePosted}</td>
                <td class="${priorityClass}">${priorityDisplay}</td>
                <td>
                    <button class="btn-view" onclick="viewAnnouncementDetails(
                        ${announcement.id}, 
                        '${announcement.title ? announcement.title.replace(/'/g, "\\'") : ""}', 
                        '${announcement.content ? announcement.content.replace(/'/g, "\\'") : ""}', 
                        '${announcement.class_name ? announcement.class_name.replace(/'/g, "\\'") : ""}', 
                        '${datePosted}', 
                        '${priorityDisplay}',
                        '${announcement.visibility}'
                    )">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
        `;
        container.appendChild(row);
    });
        console.log('Announcements display complete!'); // Debug
    } catch (err) {
        console.error('Error rendering announcements:', err); // Debug - catch any errors
        container.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading announcements</p>
                        <p>${err.message}</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

// Function to view announcement details
function viewAnnouncementDetails(id, title, content, className, datePosted, priority, visibility) {
    // Create modal on-the-fly if it doesn't exist
    let announcementModal = document.getElementById('viewAnnouncementModal');
    
    if (!announcementModal) {
        announcementModal = document.createElement('div');
        announcementModal.id = 'viewAnnouncementModal';
        announcementModal.className = 'modal';
        document.body.appendChild(announcementModal);
    }
    
    // Set priority class
    let priorityClass = '';
    if (priority === 'Urgent') {
        priorityClass = 'priority-urgent';
    } else if (priority === 'Important') {
        priorityClass = 'priority-important';
    }
    
    // Format visibility for display
    const visibilityDisplay = visibility === 'parents' ? 
        '<span style="background-color: #3498db; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;"><i class="fas fa-user-friends"></i> For Parents</span>' : 
        '';
    
    // Create modal content
    announcementModal.innerHTML = `
        <div class="modal-content announcement-modal">
            <span class="close-modal" onclick="closeAnnouncementModal()">&times;</span>
            <div class="announcement-header ${priorityClass}">
                <h3 class="modal-title">${title}</h3>
                <div class="announcement-meta">
                    <span><i class="fas fa-chalkboard"></i> ${className}</span>
                    <span><i class="fas fa-calendar"></i> ${datePosted}</span>
                    <span class="${priorityClass}"><i class="fas fa-flag"></i> ${priority}</span>
                    ${visibilityDisplay}
                </div>
            </div>
            <div class="announcement-body">
                ${content.replace(/\n/g, '<br>')}
            </div>
            <div class="announcement-files" id="announcement-files-${id}">
                <div class="loading">
                    <i class="fas fa-spinner"></i> Loading attachments...
                </div>
            </div>
        </div>
    `;
    
    // Show the modal
    announcementModal.style.display = 'block';
    
    // Load files for this announcement
    loadAnnouncementFiles(id);
}

// Function to load announcement files
function loadAnnouncementFiles(announcementId) {
    const filesContainer = document.getElementById(`announcement-files-${announcementId}`);
    
    if (!filesContainer) return;
    
    fetch(`get_announcement_files.php?announcement_id=${announcementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.files && data.files.length > 0) {
                let filesHTML = '<h4>Attachments</h4>';
                
                data.files.forEach(file => {
                    let fileIcon = 'fa-file';
                    if (file.file_type.includes('pdf')) {
                        fileIcon = 'fa-file-pdf';
                    } else if (file.file_type.includes('image')) {
                        fileIcon = 'fa-file-image';
                    } else if (file.file_type.includes('word') || file.file_type.includes('doc')) {
                        fileIcon = 'fa-file-word';
                    }
                    
                    const fileSizeDisplay = formatFileSize(file.file_size);
                    
                    filesHTML += `
                        <div class="file-item">
                            <i class="fas ${fileIcon}"></i>
                            <span class="file-name">${file.file_name} (${fileSizeDisplay})</span>
                            <a href="${file.file_path}" class="file-download" target="_blank" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    `;
                });
                
                filesContainer.innerHTML = filesHTML;
            } else {
                filesContainer.innerHTML = '<p class="no-files">No attachments for this announcement.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading announcement files:', error);
            filesContainer.innerHTML = '<p class="no-files">Error loading attachments.</p>';
        });
}

// Helper function to format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Function to close the announcement modal
function closeAnnouncementModal() {
    const modal = document.getElementById('viewAnnouncementModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Function to show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.style.display = 'block';

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    } else {
        // Fallback if notification element doesn't exist
        alert(message);
    }
}

// Function to handle logout
function handleLogout() {
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'parent-auth.html';
            } else {
                console.error('Logout failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
        });
}

// Function to initialize chat box
function initChatBox() {
    // Basic initialization that doesn't add event listeners
    // Event listeners will be added in the main DOMContentLoaded handler
    console.log('Chat box initialized');
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load user data when the page loads
    loadUserData();
    
    // Initialize chat box functionality
    initChatBox();
    
    // Add event listener for sending messages
    const sendButton = document.getElementById('send-message-btn');
    if (sendButton) {
        console.log('Adding click event to send button');
        // Remove any existing event listeners to prevent duplicates
        const newSendButton = sendButton.cloneNode(true);
        sendButton.parentNode.replaceChild(newSendButton, sendButton);
        newSendButton.addEventListener('click', sendMessageToTeacher);
    }
    
    // Handle text area auto-resize and key events
    const messageInput = document.getElementById('parent-message-input');
    if (messageInput) {
        console.log('Adding events to message input');
        // Remove any existing event listeners to prevent duplicates
        const newMessageInput = messageInput.cloneNode(true);
        messageInput.parentNode.replaceChild(newMessageInput, messageInput);
        
        // Auto-resize the textarea based on content
        function autoResizeTextarea() {
            newMessageInput.style.height = 'auto';
            newMessageInput.style.height = (newMessageInput.scrollHeight) + 'px';
        }
        
        // Add input event for auto-resizing
        newMessageInput.addEventListener('input', autoResizeTextarea);
        
        // Add keypress event for sending on Enter
        newMessageInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessageToTeacher();
                
                // Reset height after sending
                setTimeout(() => {
                    this.style.height = 'auto';
                }, 10);
            }
        });
    }
    
    // Add event listeners for chat box actions
    const minimizeBtn = document.getElementById('minimize-chat');
    if (minimizeBtn) {
        // Remove any existing event listeners to prevent duplicates
        const newMinimizeBtn = minimizeBtn.cloneNode(true);
        minimizeBtn.parentNode.replaceChild(newMinimizeBtn, minimizeBtn);
        newMinimizeBtn.addEventListener('click', function() {
            const chatBox = document.getElementById('chat-box');
            chatBox.classList.toggle('minimized');
            
            // Update icon based on state
            if (chatBox.classList.contains('minimized')) {
                this.innerHTML = '<i class="fas fa-expand"></i>';
                this.title = "Expand";
            } else {
                this.innerHTML = '<i class="fas fa-minus"></i>';
                this.title = "Minimize";
            }
        });
    }
    
    const closeBtn = document.getElementById('close-chat');
    if (closeBtn) {
        // Remove any existing event listeners to prevent duplicates
        const newCloseBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
        newCloseBtn.addEventListener('click', function() {
            const chatBox = document.getElementById('chat-box');
            chatBox.classList.remove('open');
        });
    }
    
    // Note: We don't add event listeners for message-teacher-btn buttons here
    // because they're dynamically created and event listeners are added in displayChildClasses
    
    // Ensure delete buttons are removed from child cards
    function removeDeleteButtons() {
        document.querySelectorAll('.child-card .btn-delete, .child-card i.fa-trash').forEach(button => {
            button.remove();
        });
    }
    
    // Run initially and then periodically to catch any newly added buttons
    removeDeleteButtons();
    setInterval(removeDeleteButtons, 1000);
});

// Function to send a message to a teacher
function sendMessageToTeacher() {
    // Get the message, class ID, and teacher ID from the chat box
    const messageInput = document.getElementById('parent-message-input');
    const message = messageInput.value.trim();
    const chatBox = document.getElementById('chat-box');
    const teacherId = chatBox.dataset.teacherId;
    const classId = chatBox.dataset.classId;
    
    console.log('Send message:', {
        message: message, 
        messageLength: message.length,
        teacherId: teacherId,
        classId: classId,
        messageInputValue: messageInput.value
    });
    
    // Validate inputs
    if (!message) {
        console.log('Message validation failed - empty message');
        showNotification('Please enter a message', 'error');
        return;
    }
    
    if (!teacherId || !classId) {
        console.log('Teacher/Class validation failed', {teacherId, classId});
        showNotification('Missing teacher or class information', 'error');
        return;
    }
    
    // Get send button and show loading state
    const sendButton = document.getElementById('send-message-btn');
    if (sendButton) {
        sendButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    // Clear the input field immediately for better UX
    messageInput.value = '';
    messageInput.style.height = 'auto'; // Reset height
    
    // Optimistically add the message to the UI
    const chatMessages = document.getElementById('chat-messages');
    const now = new Date();
    const formattedTime = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    
    const messageElement = document.createElement('div');
    messageElement.className = 'chat-message sent';
    messageElement.innerHTML = `
        <div class="message-bubble">
            <div class="message-text">${message}</div>
            <div class="message-time">${formattedTime}</div>
        </div>
    `;
    
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Send the message to the server
    console.log('Sending message to server:', {teacherId, classId, message});
    
    fetch('send_parent_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            teacher_id: teacherId,
            class_id: classId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
    
        // Reset send button
        if (sendButton) {
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
        
        if (!data.success) {
            console.error('Error sending message:', data.message);
            showNotification('Error sending message: ' + data.message, 'error');
            
            // Add error indicator to the message
            messageElement.querySelector('.message-bubble').classList.add('error');
            messageElement.querySelector('.message-time').innerHTML += ' <i class="fas fa-exclamation-circle" title="Failed to send"></i>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while sending the message', 'error');
        
        // Reset send button
        if (sendButton) {
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
        
        // Add error indicator to the message
        messageElement.querySelector('.message-bubble').classList.add('error');
        messageElement.querySelector('.message-time').innerHTML += ' <i class="fas fa-exclamation-circle" title="Failed to send"></i>';
    });
    
    // Focus back on the input for continuous messaging
    setTimeout(() => {
        messageInput.focus();
    }, 100);
}

// Function to open chat with a teacher
function openTeacherChat(teacherId, classId, teacherName, className) {
    console.log('openTeacherChat called with:', {teacherId, classId, teacherName, className});
    
    // Set chat box data
    const chatBox = document.getElementById('chat-box');
    chatBox.dataset.teacherId = teacherId;
    chatBox.dataset.classId = classId;
    
    console.log('Chat box dataset after update:', chatBox.dataset);
    
    // Update chat title
    document.getElementById('chat-title-text').textContent = 'Message to ' + teacherName;
    
    // Show the chat box
    chatBox.classList.add('open');
    chatBox.classList.remove('minimized');
    
    // Reset minimize button if it was changed
    const minimizeBtn = document.getElementById('minimize-chat');
    if (minimizeBtn) {
        minimizeBtn.innerHTML = '<i class="fas fa-minus"></i>';
        minimizeBtn.title = "Minimize";
    }
    
    // Clear previous messages and show loading
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading messages...</p>
        </div>
    `;
    
    // Focus the input field
    setTimeout(() => {
        const messageInput = document.getElementById('parent-message-input');
        if (messageInput) {
            messageInput.focus();
        }
    }, 300);
    
    // Construct the API URL with debugging
    const apiUrl = `get_parent_teacher_messages.php?teacher_id=${teacherId}&class_id=${classId}`;
    console.log('Fetching messages from:', apiUrl);
    
    // Load existing messages
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            console.log('API response:', data);
            if (data.success) {
                displayTeacherChatMessages(data.messages);
            } else {
                chatMessages.innerHTML = `
                    <div class="empty-state">
                        <p>Error loading messages: ${data.message}</p>
                    </div>
                `;
                console.error('Failed to load messages:', data.message);
            }
        })
        .catch(error => {
            chatMessages.innerHTML = `
                <div class="empty-state">
                    <p>Error loading messages. Please try again later.</p>
                </div>
            `;
            console.error('Error loading messages:', error);
        });
}

// Function to display teacher chat messages
function displayTeacherChatMessages(messages) {
    const chatMessages = document.getElementById('chat-messages');
    
    if (!messages || messages.length === 0) {
        chatMessages.innerHTML = `
            <div class="empty-state">
                <p>No messages yet. Start the conversation!</p>
            </div>
        `;
        return;
    }
    
    // Group messages by date
    const messagesByDate = {};
    
    messages.forEach(message => {
        const date = message.formatted_date;
        if (!messagesByDate[date]) {
            messagesByDate[date] = [];
        }
        messagesByDate[date].push(message);
    });
    
    // Create HTML for messages
    let messagesHTML = '';
    
    for (const date in messagesByDate) {
        messagesHTML += `<div class="chat-date-separator"><span>${date}</span></div>`;
        
        messagesByDate[date].forEach(message => {
            const isParent = message.sender_type === 'parent';
            const messageClass = isParent ? 'sent' : 'received';
            
            messagesHTML += `
                <div class="chat-message ${messageClass}">
                    <div class="message-bubble">
                        <div class="message-text">${message.message}</div>
                        <div class="message-time">${message.formatted_time}</div>
                    </div>
                </div>
            `;
        });
    }
    
    chatMessages.innerHTML = messagesHTML;
    
    // Scroll to the bottom of the messages
    chatMessages.scrollTop = chatMessages.scrollHeight;
} 