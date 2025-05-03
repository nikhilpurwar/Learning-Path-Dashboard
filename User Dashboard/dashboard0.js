

// ======================
// Enhanced Authentication Check
// ======================
document.addEventListener('DOMContentLoaded', () => {
  const user = JSON.parse(localStorage.getItem('user'));
  if (user) updateUserProfileDisplay(user);
});

(async function checkAuth() {
    // First check if we're running locally (development environment)
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    // If not localhost and trying to access directly, force redirect
    if (!isLocalhost && window.location.pathname.endsWith('dashboard.html')) {
      window.location.href = '../index.html?invalid_access=1';
      return;
    }
  
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = '../index.html?auth_required=1';
        return;
    }
  
    try {
        const response = await fetch('../../Backend/check_auth.php', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Auth check failed');
        
        const data = await response.json();
        if (!data.success) throw new Error('Not authenticated');
        
        // If authentication is successful, call initDashboard and pass data
        initDashboard(data);  // Pass the response data here
        
    } catch (error) {
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
        window.location.href = '../index.html?session_expired=1';
    }
})();

// ======================
// DOM Elements
// ======================
const domainTabsContainer = document.querySelector('.domain-tabs');
const subjectTabsContainer = document.querySelector('.subject-tabs');
const courseGridContainer = document.querySelector('.course-grid');
const currentCategorySpan = document.querySelector('.current-category span');
const profileIcon = document.querySelector('.profile-icon');
const dropdownMenu = document.querySelector('.dropdown-menu');

// ======================
// State Management
// ======================
let currentDomain = null;
let currentSubject = null;
let courseData = { domains: [] };
let isLoading = false;
z
// ======================
// Initialization
// ======================
// Updated initDashboard to accept `data` as parameter
async function initDashboard(data) {
  if (data.user) {
    localStorage.setItem('user', JSON.stringify(data.user));
    updateUserProfileDisplay(data.user);  // Update the profile display
  }

  try {
      await loadDashboardData();
      renderDomainTabs();
      setupEventListeners();
      
      // Set first domain as active by default if available
      if (courseData.domains.length > 0) {
          setActiveDomain(courseData.domains[0].id);
      }
  } catch (error) {
      console.error("Dashboard initialization failed:", error);
      showAlert("Failed to load dashboard data", "error");
  }
}

// ======================
// Function to update the user's profile info on the dashboard
function updateUserProfileDisplay(user) {
  if (!user) return;

  const profileNameEl = document.querySelector('.profile-name'); // if using
  const profileIconEl = document.querySelector('.profile-icon');

  // Set name if available
  if (profileNameEl) profileNameEl.textContent = user.name;

  // Set profile picture if available
  if (user.profile_pic && profileIconEl) {
      profileIconEl.innerHTML = `<img src="data:image/png;base64,${user.profile_pic}" alt="Profile Picture" class="user-avatar">`;
  }
}


// ======================
// Domain/Subject Handling (NEW FUNCTIONS ADDED)
// ======================
function setActiveDomain(domainId) {
    // Set loading state
    courseGridContainer.innerHTML = '<div class="loading-animation">Loading subjects...</div>';
    
    // Simulate network delay
    setTimeout(() => {
      currentDomain = domainId;
      currentSubject = null;
      
      // Update active state of domain tabs
      document.querySelectorAll('.domain-tab').forEach(tab => {
        const isActive = tab.dataset.domainId === domainId;
        tab.classList.toggle('active', isActive);
        if (isActive) {
          const domain = courseData.domains.find(d => d.id === domainId);
          tab.style.backgroundColor = domain.color;
          tab.style.borderColor = domain.color;
          tab.style.color = 'white';
        } else {
          tab.style.backgroundColor = 'white';
          tab.style.color = 'var(--deep-space)';
        }
      });
      
      // Render subjects for this domain
      renderSubjectTabs();
      
      // Clear courses until subject is selected
      const domain = courseData.domains.find(d => d.id === domainId);
      currentCategorySpan.textContent = domain.name;
      courseGridContainer.innerHTML = '<p class="select-subject-prompt"><i class="fas fa-hand-pointer"></i> Select a subject to view available courses</p>';
    }, 500);
  }
  function renderSubjectTabs() {
    subjectTabsContainer.innerHTML = '';
    
    const domain = courseData.domains.find(d => d.id === currentDomain);
    if (!domain) return;
    
    domain.subjects.forEach(subject => {
      const tab = document.createElement('button');
      tab.className = `subject-tab ${subject.id === currentSubject ? 'active' : ''}`;
      tab.textContent = subject.name;
      tab.dataset.subjectId = subject.id;
      
      tab.addEventListener('click', () => {
        setActiveSubject(subject.id);
      });
      
      subjectTabsContainer.appendChild(tab);
    });
  }

  function setActiveSubject(subjectId) {
    // Set loading state
    courseGridContainer.innerHTML = '<div class="loading-animation">Loading courses...</div>';
    
    // Simulate network delay
    setTimeout(() => {
      currentSubject = subjectId;
      
      // Update active state of subject tabs
      document.querySelectorAll('.subject-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.subjectId === subjectId);
      });
      
      // Render courses for this subject
      renderCourseGrid();
    }, 800);
  }

// ======================
// Course Rendering (NEW FUNCTION ADDED)
// ======================

function renderCourseGrid() {
    courseGridContainer.innerHTML = '';
    
    const domain = courseData.domains.find(d => d.id === currentDomain);
    if (!domain) return;
    
    const subject = domain.subjects.find(s => s.id === currentSubject);
    if (!subject || !subject.courses) return;
    
    if (subject.courses.length === 0) {
      courseGridContainer.innerHTML = '<p class="no-courses-message"><i class="fas fa-book"></i> No courses available for this subject yet</p>';
      return;
    }
    
    // Update current category display
    currentCategorySpan.textContent = `${domain.name}: ${subject.name}`;
    
    subject.courses.forEach(course => {
      const courseCard = document.createElement('div');
      courseCard.className = 'course-card';
      courseCard.innerHTML = `
        <div class="course-image">
          <img src="${course.image}" alt="${course.title}">
          <span class="category-badge" style="background-color: ${domain.color}">${domain.name}</span>
        </div>
        <div class="course-info">
          <h3 class="course-title">${course.title}</h3>
          <p class="course-author">By ${course.author}</p>
          <div class="course-rating">
            ${renderRatingStars(course.rating)} (${course.reviews} reviews)
          </div>
          <div class="course-meta">
            <div class="course-price">$${course.price.toFixed(2)}</div>
            <button class="purchase-btn">Enroll Now</button>
          </div>
        </div>
      `;
      
      // Add click event to purchase button
      const purchaseBtn = courseCard.querySelector('.purchase-btn');
      purchaseBtn.addEventListener('click', () => {
        alert(`Added "${course.title}" to your cart!`);
      });
      
      courseGridContainer.appendChild(courseCard);
    });
  }

// ======================
// Helper Functions (NEW FUNCTIONS ADDED)
// ======================
function getCurrentDomain() {
  return courseData.domains.find(d => d.id === currentDomain);
}

function transformApiData(apiData) {
  // Transform API response to match expected format
  return apiData.domains || [];
}

// ======================
// Existing Functions (UNCHANGED)
// ======================

function renderDomainTabs() {
    domainTabsContainer.innerHTML = '';
    
    courseData.domains.forEach(domain => {
      const tab = document.createElement('button');
      tab.className = `domain-tab ${domain.id === currentDomain ? 'active' : ''}`;
      tab.innerHTML = `<i class="${domain.icon}"></i> ${domain.name}`;
      tab.dataset.domainId = domain.id;
      tab.style.backgroundColor = domain.color;
      tab.style.borderColor = domain.color;
      
      tab.addEventListener('click', () => {
        setActiveDomain(domain.id);
      });
      
      domainTabsContainer.appendChild(tab);
    });
  }



async function loadDashboardData() {
  if (isLoading) return;
  isLoading = true;
  
  try {
      const apiData = await fetchDashboardData();
      if (apiData) {
          courseData.domains = transformApiData(apiData);
          return;
      }
  } catch (error) {
      console.error("API load failed:", error);
  }
  
  // Fallback to local data if API fails
  loadFallbackData();
  isLoading = false;
}

function renderDomainTabs() {
  domainTabsContainer.innerHTML = '';
  
  if (!courseData.domains || courseData.domains.length === 0) {
      domainTabsContainer.innerHTML = `
          <div class="empty-state">
              <i class="fas fa-book-open"></i>
              <p>No categories available</p>
          </div>
      `;
      return;
  }
  
  courseData.domains.forEach(domain => {
      const tab = document.createElement('button');
      tab.className = `domain-tab ${domain.id === currentDomain ? 'active' : ''}`;
      tab.innerHTML = `
          <i class="${domain.icon}"></i>
          <span>${domain.name}</span>
      `;
      tab.dataset.domainId = domain.id;
      tab.style.setProperty('--domain-color', domain.color);
      
      tab.addEventListener('click', () => setActiveDomain(domain.id));
      domainTabsContainer.appendChild(tab);
  });
}

// ======================
// Data Loading
// ======================
async function loadDashboardData() {
  if (isLoading) return;
  isLoading = true;
  
  try {
      const apiData = await fetchDashboardData();
      if (apiData) {
          courseData.domains = transformApiData(apiData);
          return;
      }
  } catch (error) {
      console.error("API load failed:", error);
  }
  
  // Fallback to local data if API fails
  loadFallbackData();
  isLoading = false;
}

// Enhanced with more domains and courses
function loadFallbackData() {
  courseData.domains = [
      {
          id: 'web-dev',
          name: 'Web Development',
          icon: 'fas fa-code',
          color: '#6C5CE7',
          subjects: [
              {
                  id: 'html-css',
                  name: 'HTML & CSS',
                  courses: [
                      {
                          id: 'html-fundamentals',
                          title: 'HTML Fundamentals',
                          author: 'Sarah Johnson',
                          rating: 4.5,
                          reviews: 128,
                          price: 29.99,
                          image: '../../Asset/Courses/html.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('html-fundamentals-progress')) || 0
                      },
                      {
                          id: 'css-mastery',
                          title: 'CSS Mastery',
                          author: 'Mike Chen',
                          rating: 4.7,
                          reviews: 95,
                          price: 39.99,
                          image: '../../Asset/Courses/css.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('css-mastery-progress')) || 0
                      },
                      {
                          id: 'responsive-design',
                          title: 'Responsive Design',
                          author: 'Emma Wilson',
                          rating: 4.6,
                          reviews: 87,
                          price: 34.99,
                          image: '../../Asset/Courses/responsive.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('responsive-design-progress')) || 0
                      }
                  ]
              },
              {
                  id: 'javascript',
                  name: 'JavaScript',
                  courses: [
                      {
                          id: 'js-basics',
                          title: 'JavaScript Basics',
                          author: 'Alex Rivera',
                          rating: 4.8,
                          reviews: 156,
                          price: 49.99,
                          image: '../../Asset/Courses/js.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('js-basics-progress')) || 0
                      },
                      {
                          id: 'advanced-js',
                          title: 'Advanced JavaScript',
                          author: 'Priya Patel',
                          rating: 4.9,
                          reviews: 112,
                          price: 59.99,
                          image: '../../Asset/Courses/advanced-js.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('advanced-js-progress')) || 0
                      },
                      {
                          id: 'es6-features',
                          title: 'ES6 Features',
                          author: 'David Kim',
                          rating: 4.7,
                          reviews: 78,
                          price: 44.99,
                          image: '../../Asset/Courses/es6.png',
                          category: 'web-dev',
                          progress: parseInt(localStorage.getItem('es6-features-progress')) || 0
                      }
                  ]
              }
          ]
      },
      {
          id: 'data-science',
          name: 'Data Science',
          icon: 'fas fa-chart-bar',
          color: '#00CEFF',
          subjects: [
              {
                  id: 'python-ds',
                  name: 'Python for DS',
                  courses: [
                      {
                          id: 'python-basics',
                          title: 'Python Basics',
                          author: 'Raj Patel',
                          rating: 4.6,
                          reviews: 143,
                          price: 34.99,
                          image: '../../Asset/Courses/python.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('python-basics-progress')) || 0
                      },
                      {
                          id: 'pandas-course',
                          title: 'Pandas Mastery',
                          author: 'Lisa Wong',
                          rating: 4.8,
                          reviews: 98,
                          price: 49.99,
                          image: '../../Asset/Courses/pandas.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('pandas-course-progress')) || 0
                      },
                      {
                          id: 'data-visualization',
                          title: 'Data Visualization',
                          author: 'Carlos Ruiz',
                          rating: 4.5,
                          reviews: 76,
                          price: 39.99,
                          image: '../../Asset/Courses/dataviz.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('data-visualization-progress')) || 0
                      }
                  ]
              },
              {
                  id: 'machine-learning',
                  name: 'Machine Learning',
                  courses: [
                      {
                          id: 'ml-fundamentals',
                          title: 'ML Fundamentals',
                          author: 'Nadia Ali',
                          rating: 4.7,
                          reviews: 132,
                          price: 59.99,
                          image: '../../Asset/Courses/ml.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('ml-fundamentals-progress')) || 0
                      },
                      {
                          id: 'tensorflow-course',
                          title: 'TensorFlow Basics',
                          author: 'James Wilson',
                          rating: 4.6,
                          reviews: 88,
                          price: 54.99,
                          image: '../../Asset/Courses/tensorflow.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('tensorflow-course-progress')) || 0
                      },
                      {
                          id: 'nlp-intro',
                          title: 'NLP Introduction',
                          author: 'Sophia Chen',
                          rating: 4.9,
                          reviews: 67,
                          price: 49.99,
                          image: '../../Asset/Courses/nlp.png',
                          category: 'data-science',
                          progress: parseInt(localStorage.getItem('nlp-intro-progress')) || 0
                      }
                  ]
              }
          ]
      },
      {
          id: 'mobile-dev',
          name: 'Mobile Development',
          icon: 'fas fa-mobile-alt',
          color: '#FD79A8',
          subjects: [
              {
                  id: 'flutter',
                  name: 'Flutter',
                  courses: [
                      {
                          id: 'flutter-basics',
                          title: 'Flutter Basics',
                          author: 'Thomas Baker',
                          rating: 4.6,
                          reviews: 121,
                          price: 49.99,
                          image: '../../Asset/Courses/flutter.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('flutter-basics-progress')) || 0
                      },
                      {
                          id: 'flutter-ui',
                          title: 'Flutter UI Design',
                          author: 'Maria Garcia',
                          rating: 4.8,
                          reviews: 94,
                          price: 54.99,
                          image: '../../Asset/Courses/flutter-ui.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('flutter-ui-progress')) || 0
                      },
                      {
                          id: 'flutter-firebase',
                          title: 'Flutter with Firebase',
                          author: 'Kevin Lee',
                          rating: 4.7,
                          reviews: 78,
                          price: 59.99,
                          image: '../../Asset/Courses/flutter-firebase.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('flutter-firebase-progress')) || 0
                      }
                  ]
              },
              {
                  id: 'react-native',
                  name: 'React Native',
                  courses: [
                      {
                          id: 'rn-basics',
                          title: 'React Native Basics',
                          author: 'Emma Johnson',
                          rating: 4.5,
                          reviews: 112,
                          price: 44.99,
                          image: '../../Asset/Courses/react-native.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('rn-basics-progress')) || 0
                      },
                      {
                          id: 'rn-advanced',
                          title: 'Advanced React Native',
                          author: 'Daniel Kim',
                          rating: 4.7,
                          reviews: 86,
                          price: 54.99,
                          image: '../../Asset/Courses/rn-advanced.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('rn-advanced-progress')) || 0
                      },
                      {
                          id: 'rn-performance',
                          title: 'RN Performance',
                          author: 'Sophie Martin',
                          rating: 4.6,
                          reviews: 64,
                          price: 49.99,
                          image: '../../Asset/Courses/rn-performance.png',
                          category: 'mobile-dev',
                          progress: parseInt(localStorage.getItem('rn-performance-progress')) || 0
                      }
                  ]
              }
          ]
      }
  ];
}


// ======================
// Course Enrollment
// ======================
async function handleCourseAction(courseId, isContinue) {
  const buttons = document.querySelectorAll(`.purchase-btn`);
  const button = Array.from(buttons).find(btn => 
      btn.closest('.course-card').querySelector('img').alt.includes(courseId)
  );
  
  if (!button) return;
  
  const originalText = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  
  try {
      const token = localStorage.getItem('authToken');
      if (!token) throw new Error('Not authenticated');

      const response = await fetch('../../Backend/enroll.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ 
              courseId,
              isContinue: isContinue ? 1 : 0 
          })
      });
      
      if (!response.ok) throw new Error('Request failed');
      
      const result = await response.json();
      if (!result.success) throw new Error(result.message || 'Action failed');
      
      showAlert(`Course ${isContinue ? 'continued' : 'enrolled'} successfully!`, 'success');
      await loadDashboardData(); // Refresh data
      
  } catch (error) {
      console.error('Action failed:', error);
      showAlert(error.message || 'Action failed. Please try again.', 'error');
  } finally {
      button.disabled = false;
      button.innerHTML = originalText;
  }
}

// ======================
// Helper Functions
// ======================

function renderRatingStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    
    // Full stars
    stars += '<i class="fas fa-star"></i>'.repeat(fullStars);
    
    // Half star
    if (hasHalfStar) stars += '<i class="fas fa-star-half-alt"></i>';
    
    // Empty stars
    stars += '<i class="far fa-star"></i>'.repeat(emptyStars);
    
    return stars;
  }


function showAlert(message, type = 'success') {
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.setAttribute('role', 'alert');
  alert.innerHTML = `
      <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
      ${message}
  `;
  
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 5000);
}

function setupEventListeners() {
  // Profile dropdown
  profileIcon?.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownMenu?.classList.toggle('show');
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', () => {
      dropdownMenu?.classList.remove('show');
  });
}