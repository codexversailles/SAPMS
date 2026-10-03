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

// Function to update user profile in the sidebar
function updateUserProfile(parent) {
    // Update user name
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach(element => {
        element.textContent = parent.full_name;
    });
    
    // Update welcome message
    const welcomeMessage = document.querySelector('.dashboard-card .card-title');
    if (welcomeMessage) {
        welcomeMessage.textContent = `Welcome Back, ${parent.full_name}!`;
    }
}

// Function to update dashboard content
function updateDashboardContent(data) {
    // Update children section
    const childrenContainer = document.getElementById('children-container');
    if (childrenContainer) {
        if (data.parent && data.parent.student_id) {
            // Create child card
            const childCard = document.createElement('a');
            childCard.href = `#child-${data.parent.student_id}`;
            childCard.className = 'child-card';
            childCard.innerHTML = `
                <div class="child-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="child-name">${data.parent.student_full_name}</div>
                <div class="child-id">ID: ${data.parent.student_id}</div>
                <div class="child-relationship">${data.parent.relationship}</div>
            `;
            
            // Add click event to navigate to child details
            childCard.addEventListener('click', function(e) {
                e.preventDefault();
                const childId = this.getAttribute('href').substring(1);
                navigateToSection(childId);
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
    
    // This function will be expanded in the future to populate:
    // 1. Recent grades
    // 2. Attendance records
    // 3. Messages
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
                window.location.href = 'parent-auth.html';
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
    
    // Set up navigation event listeners
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const sectionId = href.substring(1);
                navigateToSection(sectionId);
            } else if (href === 'parent-auth.html') {
                e.preventDefault();
                handleLogout();
            }
        });
    });
    
    // Set up notification button
    const notificationBtn = document.querySelector('.btn-primary');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // This will be implemented in the future
            alert('Notifications feature coming soon!');
        });
    }
}); 