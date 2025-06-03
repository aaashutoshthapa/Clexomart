
        function closeInvoice() {
            document.getElementById('invoiceModal').style.display = 'none';
        }

        // Function to populate invoice data (can be called after payment)
        function populateInvoice(data) {
            document.getElementById('invoiceNumber').textContent = data.invoiceNumber || 'INV-001';
            document.getElementById('invoiceDate').textContent = data.date || 'April 24, 2025';
            document.getElementById('paymentMethod').textContent = data.paymentMethod || 'Credit Card';
            document.getElementById('userName').textContent = data.userName || 'User Name';
            document.getElementById('phoneNumber').textContent = data.phoneNumber || 'Phone No';

            const itemsBody = document.getElementById('invoiceItems');
            itemsBody.innerHTML = '';
            let subtotal = 0;

            data.items.forEach(item => {
                const amount = item.quantity * item.price;
                subtotal += amount;
                const row = `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>$${item.price.toFixed(2)}</td>
                        <td>$${amount.toFixed(2)}</td>
                    </tr>
                `;
                itemsBody.innerHTML += row;
            });

            const tax = subtotal * 0.1; // Assuming 10% tax
            const total = subtotal + tax;

            document.querySelector('.totals p:nth-child(1)').textContent = `Sub Total: $${subtotal.toFixed(2)}`;
            document.querySelector('.totals p:nth-child(2)').textContent = `Tax: $${tax.toFixed(2)}`;
            document.querySelector('.totals p:nth-child(3)').textContent = `TOTAL: $${total.toFixed(2)}`;
        }

        // Example usage after payment
        // populateInvoice({
        //     invoiceNumber: 'INV-001',
        //     date: 'April 24, 2025',
        //     paymentMethod: 'Credit Card',
        //     userName: 'John Doe',
        //     phoneNumber: '+977-9800000000',
        //     items: [
        //         { name: 'Mixed Apples', quantity: 1, price: 5.00 },
        //         { name: 'Cheese', quantity: 1, price: 7.00 },
        //         { name: 'Chicken Breast', quantity: 1, price: 5.00 },
        //         { name: 'Potato', quantity: 1, price: 3.00 }
        //     ]
        // });
  