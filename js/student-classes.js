document.addEventListener('DOMContentLoaded', function() {
    const classesContainer = document.getElementById('classesContainer');
    
    if (classesContainer) {
        loadStudentClasses();
    }

    async function loadStudentClasses() {
        try {
            const response = await fetch('get_student_classes.php');
            const data = await response.json();

            if (data.success) {
                displayClasses(data.classes);
            } else {
                showError('Failed to load classes');
            }
        } catch (error) {
            showError('An error occurred while loading classes');
        }
    }

    function displayClasses(classes) {
        if (classes.length === 0) {
            classesContainer.innerHTML = `
                <div class="text-center py-5">
                    <h4>You haven't joined any classes yet</h4>
                    <p class="text-muted">Use the form above to join a class using a class code</p>
                </div>
            `;
            return;
        }

        classesContainer.innerHTML = classes.map(classItem => `
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${classItem.class_name}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            ${classItem.teacher_first_name} ${classItem.teacher_last_name}
                        </h6>
                        <p class="card-text">${classItem.description || 'No description available'}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">${classItem.class_code}</span>
                            <a href="class_details.php?id=${classItem.id}" class="btn btn-outline-primary btn-sm">
                                View Class
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function showError(message) {
        classesContainer.innerHTML = `
            <div class="alert alert-danger" role="alert">
                ${message}
            </div>
        `;
    }
}); 