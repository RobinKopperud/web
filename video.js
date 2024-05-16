document.addEventListener('DOMContentLoaded', function() {
    const languageSelect = document.getElementById('language-select');
    const videoFrame = document.getElementById('video-frame');

    const videoLinks = {
        fr: 'https://www.youtube.com/embed/1k5c1FzSVBw',
        sv: 'https://www.youtube.com/embed/mIKZjKDG7ks',
        no: 'https://www.youtube.com/embed/fvayazBsm0k', // Original video for Norwegian
        da: 'https://www.youtube.com/embed/vqIVGnf88fs'
    };

    // Set initial video
    videoFrame.src = videoLinks.no;

    languageSelect.addEventListener('change', function() {
        const selectedLanguage = languageSelect.value;
        videoFrame.src = videoLinks[selectedLanguage];
    });
});
