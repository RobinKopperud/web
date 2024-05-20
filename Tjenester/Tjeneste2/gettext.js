document.getElementById('upload-image').addEventListener('click', () => {
    const imageUpload = document.getElementById('upload-image').files[0];

    if (!imageUpload) {
        alert('Please select an image file first.');
        return;
    }

    const reader = new FileReader();
    reader.onloadend = async () => {
        const base64Image = reader.result.split(',')[1]; // Extract base64 part

        const formData = new FormData();
        formData.append('image', base64Image);

        try {
            const response = await fetch('upload.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to fetch data from server');
            }

            const result = await response.json();
            document.getElementById('result').textContent = `License Plate Number: ${result.choices[0].message.content}`;
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while processing the image.');
        }
    };
    reader.readAsDataURL(imageUpload);
});
