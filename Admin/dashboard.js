document.getElementById('logoutBtn').addEventListener('click', () => {
  fetch('logout.php').then(() => {
    window.location.href = '../index.html';
  });
});

const fetchProgress = () => {
  fetch('get_progress.php')
    .then(response => response.json())
    .then(data => {
      data.forEach(progress => {
        const progressElement = document.getElementById(`progress-${progress.topic}`);
        if (progressElement) {
          progressElement.innerText = `${progress.progress}%`;
        }
      });
    });
};

const addCourse = () => {
  const subject = document.getElementById('subject').value;
  const courseName = document.getElementById('courseName').value;
  const author = document.getElementById('author').value;
  const description = document.getElementById('description').value;
  const price = document.getElementById('price').value;
  const category = document.getElementById('category').value;
  const resourceLink = document.getElementById('resourceLink').value;
  const videoLink = document.getElementById('videoLink').value;
  const uploadResource = document.getElementById('uploadResource').files[0];
  const courseImage = document.getElementById('courseImage').files[0];

  const formData = new FormData();
  formData.append('subject', subject);
  formData.append('title', courseName);
  formData.append('author', author);
  formData.append('description', description);
  formData.append('price', price);
  formData.append('category', category);
  formData.append('resource_link', resourceLink);
  formData.append('video_link', videoLink);
  formData.append('upload_resource', uploadResource);
  formData.append('image', courseImage);

  fetch('add_course.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.text())
    .then(data => {
      alert(data);
    });
};

fetchProgress();

// Optional: Trigger addCourse on form submission
document.getElementById('pathForm').addEventListener('submit', (e) => {
  e.preventDefault();
  addCourse();
});
