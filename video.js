document.addEventListener('DOMContentLoaded', function () {
    const languageSelect = document.getElementById('language-select');
    const videoFrame = document.getElementById('video-frame');

    const videos = {
        fr: 'https://www.youtube.com/embed/1k5c1FzSVBw',
        sv: 'https://www.youtube.com/embed/mIKZjKDG7ks',
        no: 'https://www.youtube.com/embed/mXY1RWmEwsI', // Original video for Norwegian
        da: 'https://www.youtube.com/embed/vqIVGnf88fs',
        en: 'https://www.youtube.com/embed/Z6XRqcpU5dY',
        OL: 'https://www.youtube.com/embed/iwzyF4P7CCQ'
    };

    languageSelect.addEventListener('change', function () {
        const selectedLanguage = this.value;
        if (videos[selectedLanguage]) {
            videoFrame.src = videos[selectedLanguage];
        }
    });

    // Set initial video
    videoFrame.src = videos[languageSelect.value];
});
