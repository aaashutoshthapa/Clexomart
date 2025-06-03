// document.addEventListener('DOMContentLoaded', () => {
//     const form = document.getElementById('signup-form');
//     const firstNameInput = document.getElementById('first-name');
//     const lastNameInput = document.getElementById('last-name');
//     const emailInput = document.getElementById('email');
//     const contactNoInput = document.getElementById('contact-no');
//     const passwordInput = document.getElementById('password');
//     const confirmPasswordInput = document.getElementById('confirm-password');
//     const roleSelect = document.getElementById('role');

//     const firstNameError = document.getElementById('first-name-error');
//     const lastNameError = document.getElementById('last-name-error');
//     const emailError = document.getElementById('email-error');
//     const contactNoError = document.getElementById('contact-no-error');
//     const passwordError = document.getElementById('password-error');
//     const confirmPasswordError = document.getElementById('confirm-password-error');
//     const roleError = document.getElementById('role-error');

//     form.addEventListener('submit', (e) => {
//         let hasErrors = false;

//         // Reset error messages
//         firstNameError.style.display = 'none';
//         lastNameError.style.display = 'none';
//         emailError.style.display = 'none';
//         contactNoError.style.display = 'none';
//         passwordError.style.display = 'none';
//         confirmPasswordError.style.display = 'none';
//         roleError.style.display = 'none';

//         // Validate First Name
//         if (!firstNameInput.value.trim()) {
//             firstNameError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Last Name
//         if (!lastNameInput.value.trim()) {
//             lastNameError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Email
//         const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//         if (!emailPattern.test(emailInput.value)) {
//             emailError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Contact Number
//         const contactPattern = /^\d{10}$/;
//         if (!contactPattern.test(contactNoInput.value)) {
//             contactNoError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Password
//         if (passwordInput.value.length < 8) {
//             passwordError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Confirm Password
//         if (passwordInput.value !== confirmPasswordInput.value) {
//             confirmPasswordError.style.display = 'block';
//             hasErrors = true;
//         }

//         // Validate Role
//         if (!roleSelect.value) {
//             roleError.style.display = 'block';
//             hasErrors = true;
//         }

//         if (hasErrors) {
//             e.preventDefault();
//         }
//     });
// });



//new code: 
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signup-form');
    const firstNameInput = document.getElementById('first-name');
    const lastNameInput = document.getElementById('last-name');
    const emailInput = document.getElementById('email');
    const contactNoInput = document.getElementById('contact-no');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const roleSelect = document.getElementById('role');

    const firstNameError = document.getElementById('first-name-error');
    const lastNameError = document.getElementById('last-name-error');
    const emailError = document.getElementById('email-error');
    const contactNoError = document.getElementById('contact-no-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    const roleError = document.getElementById('role-error');

    form.addEventListener('submit', (e) => {
        let hasErrors = false;

        // Reset error messages
        firstNameError.style.display = 'none';
        lastNameError.style.display = 'none';
        emailError.style.display = 'none';
        contactNoError.style.display = 'none';
        passwordError.style.display = 'none';
        confirmPasswordError.style.display = 'none';
        roleError.style.display = 'none';

        // Validate First Name
        if (!firstNameInput.value.trim()) {
            firstNameError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Last Name
        if (!lastNameInput.value.trim()) {
            lastNameError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Email
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(emailInput.value)) {
            emailError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Contact Number
        const contactPattern = /^\d{10}$/;
        if (!contactPattern.test(contactNoInput.value)) {
            contactNoError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Password
        if (passwordInput.value.length < 8) {
            passwordError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Confirm Password
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordError.style.display = 'block';
            hasErrors = true;
        }

        // Validate Role
        if (!roleSelect.value) {
            roleError.style.display = 'block';
            hasErrors = true;
        }

        if (hasErrors) {
            e.preventDefault();
        }
    });
});