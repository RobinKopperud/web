let licensePlateNumber = '';

document.getElementById('upload-image').addEventListener('click', () => {
    const imageUpload = document.getElementById('image-upload').files[0];

    if (!imageUpload) {
        alert('Please select an image file first.');
        return;
    }

    const reader = new FileReader();
    reader.onloadend = async () => {
        const base64String = reader.result;

        const base64Image = base64String.split(',')[1];
        if (!base64Image) {
            console.error('Invalid base64 string:', base64String);
            alert('Failed to read image data.');
            return;
        }

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
            if (result.error) {
                throw new Error(result.error);
            }

            licensePlateNumber = result.choices[0].message.content.trim().replace(/\s+/g, ''); // Set the licensePlateNumber
            document.getElementById('resultAI').textContent = `License Plate Number: ${licensePlateNumber}`;

            // Dispatch custom event with the license plate number
            const event = new CustomEvent('licensePlateObtained', { detail: { licensePlateNumber } });
            document.dispatchEvent(event);

        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while processing the image: ' + error.message);
        }
    };
    reader.readAsDataURL(imageUpload);
});
