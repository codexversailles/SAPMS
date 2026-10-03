// Smooth scroll functionality
function scrollToAbout() {
    const aboutSection = document.getElementById('about');
    aboutSection.scrollIntoView({ behavior: 'smooth' });
}

// Modal functionality
const modal = document.getElementById('roleModal');
const closeModal = document.querySelector('.close-modal');

function openModal() {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
}

function closeModalFunc() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Re-enable scrolling
}

// Close modal when clicking the X
if (closeModal) {
    closeModal.addEventListener('click', closeModalFunc);
}

// Close modal when clicking outside of it
window.addEventListener('click', (e) => {
    if (modal && e.target === modal) {
        closeModalFunc();
    }
});

// Role selection functionality
function selectRole(role) {
    // Redirect to the appropriate registration page
    switch(role) {
        case 'student':
            window.location.href = 'register-student.html';
            break;
        case 'teacher':
            window.location.href = 'register-teacher.html';
            break;
        case 'parent':
            window.location.href = 'register-parent.html';
            break;
    }
}

// Mobile menu functionality
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        hamburger.classList.toggle('active');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
            navLinks.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
            hamburger.classList.remove('active');
        });
    });
}

// Form validation for registration pages
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const teacherForm = document.getElementById('teacherForm');
    const parentForm = document.getElementById('parentForm');
    
    // Student form validation
    if (studentForm) {
        studentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // In a real application, you would send the form data to a server
            alert('Registration successful! Welcome to StudyHub, Student!');
            // Redirect to dashboard or home page
            // window.location.href = 'dashboard.html';
        });
    }
    
    // Teacher form validation
    if (teacherForm) {
        teacherForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // In a real application, you would send the form data to a server
            alert('Registration successful! Welcome to StudyHub, Teacher!');
            // Redirect to dashboard or home page
            // window.location.href = 'dashboard.html';
        });
    }
    
    // Parent form validation
    if (parentForm) {
        parentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // In a real application, you would send the form data to a server
            alert('Registration successful! Welcome to StudyHub, Parent!');
            // Redirect to dashboard or home page
            // window.location.href = 'dashboard.html';
        });
    }
});

// Form toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');

    if (toggleBtns.length && registerForm && loginForm) {
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                toggleBtns.forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                // Toggle forms
                if (this.textContent.trim().toLowerCase() === 'register') {
                    registerForm.classList.remove('hidden');
                    loginForm.classList.add('hidden');
                } else {
                    registerForm.classList.add('hidden');
                    loginForm.classList.remove('hidden');
                }
            });
        });

        // Set initial active state
        toggleBtns[0].classList.add('active');
    }
}); 