 // Mobile navbar burger menu toggle
        document.addEventListener('DOMContentLoaded', () => {
            const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            
            if ($navbarBurgers.length > 0) {
                $navbarBurgers.forEach( el => {
                    el.addEventListener('click', () => {
                        const target = el.dataset.target;
                        const $target = document.getElementById(target);
                        
                        el.classList.toggle('is-active');
                        $target.classList.toggle('is-active');
                    });
                });
            }

            // Image upload preview
            const fileInput = document.querySelector('#product-image');
            const fileName = document.querySelector('#file-name');
            const imagePreview = document.querySelector('#image-preview');
            
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    fileName.textContent = file.name;
                    
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        imagePreview.src = event.target.result;
                        imagePreview.classList.remove('is-hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Confirm before deleting product
            document.querySelectorAll('.button.is-danger').forEach(button => {
                button.addEventListener('click', (e) => {
                    if (!confirm('Are you sure you want to delete this product?')) {
                        e.preventDefault();
                    }
                });
            });
        });