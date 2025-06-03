document.getElementById('loginForm').addEventListener('submit', function(e) {
    const role = document.querySelector('select[name="role"]').value;
    if (!role) {
        alert("Please select a role.");
        e.preventDefault();
    }
});