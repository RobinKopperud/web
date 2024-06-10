function showMenu() {
    document.getElementById('menu-container').style.display = 'block';
    document.getElementById('feedback-container').style.display = 'none';
  }
  
  function showFeedback() {
    document.getElementById('menu-container').style.display = 'none';
    document.getElementById('feedback-container').style.display = 'block';
  }
  
  document.getElementById('feedback-form').addEventListener('submit', function(event) {
    event.preventDefault();
    var name = document.getElementById('name').value;
    var feedback = document.getElementById('feedback').value;
    var feedbackList = document.getElementById('feedback-list');
    var feedbackItem = document.createElement('div');
    feedbackItem.className = 'alert alert-secondary';
    feedbackItem.innerHTML = `<strong>${name}</strong>: ${feedback}`;
    feedbackList.appendChild(feedbackItem);
    document.getElementById('feedback-form').reset();
  });
  