// dashboard1.js - NeoPOP Themed Learning Path Dashboard

document.addEventListener("DOMContentLoaded", () => {
    const profileIcon = document.querySelector('.profile-icon');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (!profileIcon || !dropdownMenu) {
        console.warn("Profile icon or dropdown menu not found in the DOM.");
        return;
    }

    profileIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!dropdownMenu.contains(e.target) && !profileIcon.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    // Load user data if present
    const user = JSON.parse(localStorage.getItem("user"));
    if (user) updateUserProfileDisplay(user);
});


document.addEventListener("DOMContentLoaded", (() => {
    const user = JSON.parse(localStorage.getItem("user"));
    if (user) updateUserProfileDisplay(user);
  }));
  
  (async function () {
    if (!("localhost" === window.location.hostname || "127.0.0.1" === window.location.hostname) && window.location.pathname.endsWith("dashboard.html")) {
      window.location.href = "../index.html?invalid_access=1";
      return;
    }
    const token = localStorage.getItem("authToken");
    if (token) {
      try {
        const response = await fetch("../Backend/check_auth.php", {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json"
          }
        });
        if (!response.ok) throw new Error("Auth check failed");
        const data = await response.json();
        if (!data.success) throw new Error("Not authenticated");
        initDashboard(data);
      } catch (error) {
        localStorage.removeItem("authToken");
        localStorage.removeItem("user");
        window.location.href = "../index.html?session_expired=1";
      }
    } else {
      window.location.href = "../index.html?auth_required=1";
    }
  })();
  
  const domainTabsContainer = document.querySelector(".domain-tabs");
  const subjectTabsContainer = document.querySelector(".subject-tabs");
  const courseGridContainer = document.querySelector(".course-grid");
  const currentCategorySpan = document.querySelector(".current-category span");
//   const profileIcon = document.querySelector(".profile-icon");
//   const dropdownMenu = document.querySelector(".dropdown-menu");
  
  let currentDomain = null;
  let currentSubject = null;
  let courseData = { domains: [] };
  let isLoading = false;
  
  async function initDashboard(data) {
    if (data.user) {
      localStorage.setItem("user", JSON.stringify(data.user));
      updateUserProfileDisplay(data.user);
    }
    try {
      await loadDashboardData();
      renderDomainTabs();
      setupEventListeners();
      if (courseData.domains.length > 0) setActiveDomain(courseData.domains[0].id);
    } catch (err) {
      console.error("Dashboard initialization failed:", err);
      showAlert("Failed to load dashboard data", "error");
    }
  }
  
  function updateUserProfileDisplay(user) {
    if (!user) return;
    const nameEl = document.querySelector(".profile-name");
    const avatarEl = document.querySelector(".profile-icon");
    if (nameEl) nameEl.textContent = user.name;
    if (user.profile_pic && avatarEl) {
      avatarEl.innerHTML = `<img src="data:image/png;base64,${user.profile_pic}" alt="Profile Picture" class="user-avatar">`;
    }
  }
  
  // function setActiveDomain(domainId) {
  //   courseGridContainer.innerHTML = '<div class="loading-animation">Loading subjects...</div>';
  //   setTimeout(() => {
  //     currentDomain = domainId;
  //     currentSubject = null;
  //     document.querySelectorAll(".domain-tab").forEach(tab => {
  //       const isActive = tab.dataset.domainId === domainId;
  //       tab.classList.toggle("active", isActive);
  //       if (isActive) {
  //         const domain = courseData.domains.find(d => d.id === domainId);
  //         tab.style.backgroundColor = domain.color;
  //         tab.style.borderColor = domain.color;
  //         tab.style.color = "white";
  //       } else {
  //         tab.style.backgroundColor = "white";
  //         tab.style.color = "var(--deep-space)";
  //       }
  //     });
  //     renderSubjectTabs();
  //     const domain = courseData.domains.find(d => d.id === domainId);
  //     currentCategorySpan.textContent = domain.name;
  //     courseGridContainer.innerHTML = '<p class="select-subject-prompt"><i class="fas fa-hand-pointer"></i> Select a subject to view available courses</p>';
  //   }, 500);
  // }
  
  // function renderSubjectTabs() {
  //   subjectTabsContainer.innerHTML = "";
  //   const domain = courseData.domains.find(d => d.id === currentDomain);
  //   if (!domain) return;
  //   domain.subjects.forEach(subject => {
  //     const tab = document.createElement("button");
  //     tab.className = "subject-tab " + (subject.id === currentSubject ? "active" : "");
  //     tab.textContent = subject.name;
  //     tab.dataset.subjectId = subject.id;
  //     tab.addEventListener("click", () => setActiveSubject(subject.id));
  //     subjectTabsContainer.appendChild(tab);
  //   });
  // }
  
  // function setActiveSubject(subjectId) {
  //   courseGridContainer.innerHTML = '<div class="loading-animation">Loading courses...</div>';
  //   setTimeout(() => {
  //     currentSubject = subjectId;
  //     document.querySelectorAll(".subject-tab").forEach(tab => {
  //       tab.classList.toggle("active", tab.dataset.subjectId === subjectId);
  //     });
  //     renderCourseGrid();
  //   }, 800);
  // }
  
  // function renderCourseGrid() {
  //   courseGridContainer.innerHTML = "";
  //   const domain = courseData.domains.find(d => d.id === currentDomain);
  //   if (!domain) return;
  //   const subject = domain.subjects.find(s => s.id === currentSubject);
  //   if (subject && subject.courses && subject.courses.length > 0) {
  //     currentCategorySpan.textContent = `${domain.name}: ${subject.name}`;
  //     subject.courses.forEach(course => {
  //       const card = document.createElement("div");
  //       card.className = "course-card";
  //       card.innerHTML = `
  //         <div class="course-image">
  //           <img src="${course.image}" alt="${course.title}">
  //           <span class="category-badge" style="background-color: ${domain.color}">${domain.name}</span>
  //         </div>
  //         <div class="course-info">
  //           <h3 class="course-title">${course.title}</h3>
  //           <p class="course-author">By ${course.author}</p>
  //           <div class="course-rating">
  //             ${renderRatingStars(course.rating)} (${course.reviews} reviews)
  //           </div>
  //           <div class="course-meta">
  //             <div class="course-price">$${course.price.toFixed(2)}</div>
  //             <button class="purchase-btn">Enroll Now</button>
  //           </div>
  //         </div>
  //       `;
  //       card.querySelector(".purchase-btn").addEventListener("click", () => {
  //         alert(`Added "${course.title}" to your cart!`);
  //       });
  //       courseGridContainer.appendChild(card);
  //     });
  //   } else {
  //     courseGridContainer.innerHTML = '<p class="no-courses-message"><i class="fas fa-book"></i> No courses available for this subject yet</p>';
  //   }
  // }
  
  // function renderRatingStars(rating) {
  //   const fullStars = Math.floor(rating);
  //   const halfStar = rating % 1 >= 0.5;
  //   return `${"<i class='fas fa-star'></i>".repeat(fullStars)}${halfStar ? "<i class='fas fa-star-half-alt'></i>" : ""}`;
  // }
  
  // function renderDomainTabs() {
  //   domainTabsContainer.innerHTML = "";
  //   if (courseData.domains && courseData.domains.length > 0) {
  //     courseData.domains.forEach(domain => {
  //       const tab = document.createElement("button");
  //       tab.className = "domain-tab " + (domain.id === currentDomain ? "active" : "");
  //       tab.innerHTML = `<i class="${domain.icon}"></i><span>${domain.name}</span>`;
  //       tab.dataset.domainId = domain.id;
  //       tab.style.setProperty("--domain-color", domain.color);
  //       tab.addEventListener("click", () => setActiveDomain(domain.id));
  //       domainTabsContainer.appendChild(tab);
  //     });
  //   } else {
  //     domainTabsContainer.innerHTML = `
  //       <div class="empty-state">
  //         <i class="fas fa-book-open"></i>
  //         <p>No categories available</p>
  //       </div>`;
  //   }
  // }
  
  // async function loadDashboardData() {
  //   if (!isLoading) {
  //     isLoading = true;
  //     try {
  //       const apiData = await fetchDashboardData();
  //       if (apiData) {
  //         courseData.domains = transformApiData(apiData);
  //         return;
  //       }
  //     } catch (err) {
  //       console.error("API load failed:", err);
  //     }
  //     loadFallbackData();
  //     isLoading = false;
  //   }
  // }
  
  // function transformApiData(data) {
  //   return data.domains || [];
  // }
  
//   function setupEventListeners() {
//     profileIcon.addEventListener("click", () => {
//       dropdownMenu.classList.toggle("show");
//     });
//     window.addEventListener("click", e => {
//       if (!profileIcon.contains(e.target)) dropdownMenu.classList.remove("show");
//     });
//   }
  
  // function showAlert(message, type) {
  //   alert(`${type.toUpperCase()}: ${message}`);
  // }
  
  // function fetchDashboardData() {
  //   return fetch("../Backend/courses.php")
  //     .then(response => response.json())
  //     .catch(error => {
  //       console.error("Failed to fetch dashboard data:", error);
  //       return null;
  //     });
  // }
  
  // function loadFallbackData() {
  //   // fallback data loading logic was already included previously
  // }
  
// document.addEventListener("DOMContentLoaded", function () {
//     fetch("../Backend/get_courses.php")
//       .then((res) => res.json())
//       .then((data) => {
//         if (data.categories) {
//           renderBrowseCategories(data.categories);
//         }
//       })
//       .catch((err) => console.error("Error loading courses:", err));
//   });
  
  // function renderBrowseCategories(categories) {
  //   const container = document.getElementById("browse-categories-section");
  //   if (!container) return;
  
  //   container.innerHTML = ""; // Clear existing
  
  //   categories.forEach((cat) => {
  //     const section = document.createElement("div");
  //     section.className = "mb-8";
  
  //     const title = document.createElement("h3");
  //     title.className = "text-xl font-bold mb-4 text-indigo-600";
  //     title.textContent = cat.name;
  //     section.appendChild(title);
  
  //     const courseContainer = document.createElement("div");
  //     courseContainer.className =
  //       "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4";
  
  //     cat.courses.forEach((course) => {
  //       const card = document.createElement("div");
  //       card.className =
  //         "bg-white shadow-md rounded-2xl p-4 transition hover:scale-[1.02] duration-200";
  
  //       card.innerHTML = `
  //         <img src="../Assets/Courses/${course.image}" alt="${course.title}" class="rounded-xl mb-2 h-40 w-full object-cover">
  //         <h4 class="text-lg font-semibold text-gray-800">${course.title}</h4>
  //         <p class="text-sm text-gray-500 mb-1">${course.description || ''}</p>
  //         <div class="text-yellow-500 text-sm mb-1">⭐ ${course.rating}</div>
  //         <div class="text-green-600 font-bold text-sm">₹${course.price}</div>
  //       `;
  
  //       courseContainer.appendChild(card);
  //     });
  
  //     section.appendChild(courseContainer);
  //     container.appendChild(section);
  //   });
  // }
  
// window.onload = function() {
//     const categoriesContainer = document.querySelector('.categories-container');

//     // Iterate through each category
//     courseData.categories.forEach(category => {
//         const categorySection = document.createElement('div');
//         categorySection.classList.add('category-section');

//         // Category header (e.g., "Web Development")
//         const categoryHeader = document.createElement('h2');
//         categoryHeader.innerText = category.name;
//         categorySection.appendChild(categoryHeader);

//         // Create a container for courses under this category
//         const coursesContainer = document.createElement('div');
//         coursesContainer.classList.add('courses-container');
        
//         // Iterate through the courses in this category
//         category.courses.forEach(course => {
//             const courseCard = document.createElement('div');
//             courseCard.classList.add('course-card');

//             const courseImage = document.createElement('img');
//             courseImage.src = course.image;
//             courseImage.alt = course.title;
//             courseCard.appendChild(courseImage);

//             const courseTitle = document.createElement('h3');
//             courseTitle.innerText = course.title;
//             courseCard.appendChild(courseTitle);

//             const courseDescription = document.createElement('p');
//             courseDescription.innerText = course.description;
//             courseCard.appendChild(courseDescription);

//             const courseRating = document.createElement('p');
//             courseRating.innerText = `Rating: ${course.rating} | Price: $${course.price}`;
//             courseCard.appendChild(courseRating);

//             // Add the course card to the courses container
//             coursesContainer.appendChild(courseCard);
//         });

        // Add the courses container to the category section
//         categorySection.appendChild(coursesContainer);

//         // Add the category section to the main container
//         categoriesContainer.appendChild(categorySection);
//     });
// };

// ===================== Authentication & Redirection =====================
// const username = localStorage.getItem('username');
// const token = localStorage.getItem('token');
// if (!username || !token) window.location.href = '../index.html';

// // Redirect non-localhost users
// if (!location.hostname.includes('localhost') && location.protocol !== 'https:') {
//   location.href = 'https://' + location.hostname + location.pathname;
// }

// ===================== DOM Elements =====================
const domainTabs = document.getElementById('domain-tabs');
const subjectTabs = document.getElementById('subject-tabs');
const courseCardsContainer = document.getElementById('course-cards');
// const profileIcon = document.getElementById('profile-icon');
// const dropdownMenu = document.getElementById('dropdown-menu');
const searchInput = document.getElementById('search');
const sortSelect = document.getElementById('sort');

let dashboardData = {};
// let currentDomain = '';
// let currentSubject = '';

// ===================== Data Loading =====================
// async function loadDashboardData() {
//   try {
//     const response = await fetch('../backend/dashboard.php', {
//       method: 'POST',
//       headers: { 'Content-Type': 'application/json' },
//       body: JSON.stringify({ username, token })
//     });
//     const data = await response.json();

//     if (data.success) {
//       dashboardData = data.data;
//       renderDomains();
//     } else {
//       console.error(data.message);
//       showAlert('danger', 'Session expired. Please log in again.');
//       setTimeout(() => window.location.href = '../index.html', 2000);
//     }
//   } catch (error) {
//     console.error('Error loading dashboard data:', error);
//     showAlert('danger', 'Unable to load dashboard data. Please try again later.');
//   }
// }

// ===================== Rendering Logic =====================
// function renderDomains() {
//   domainTabs.innerHTML = '';
//   Object.keys(dashboardData).forEach(domain => {
//     const tab = document.createElement('div');
//     tab.className = `tab domain-tab ${domain === currentDomain ? 'active-tab' : ''}`;
//     tab.textContent = domain;
//     tab.onclick = () => setActiveDomain(domain);
//     domainTabs.appendChild(tab);
//   });
//   setActiveDomain(Object.keys(dashboardData)[0]);
// }

// function setActiveDomain(domain) {
//   currentDomain = domain;
//   subjectTabs.innerHTML = '';
//   Object.keys(dashboardData[domain]).forEach(subject => {
//     const tab = document.createElement('div');
//     tab.className = `tab subject-tab ${subject === currentSubject ? 'active-tab' : ''}`;
//     tab.textContent = subject;
//     tab.onclick = () => setActiveSubject(subject);
//     subjectTabs.appendChild(tab);
//   });
//   setActiveSubject(Object.keys(dashboardData[domain])[0]);
// }

// function setActiveSubject(subject) {
//   currentSubject = subject;
//   updateTabColors();
//   renderCourses();
// }

// function updateTabColors() {
//   document.querySelectorAll('.domain-tab').forEach(tab => {
//     tab.classList.toggle('active-tab', tab.textContent === currentDomain);
//   });
//   document.querySelectorAll('.subject-tab').forEach(tab => {
//     tab.classList.toggle('active-tab', tab.textContent === currentSubject);
//   });
// }

// function renderCourses() {
//   const courses = dashboardData[currentDomain][currentSubject] || [];
//   courseCardsContainer.innerHTML = '';

//   const searchTerm = searchInput.value.toLowerCase();
//   const sortedCourses = sortCourses(courses.filter(c => c.name.toLowerCase().includes(searchTerm)));

//   sortedCourses.forEach(course => {
//     const card = document.createElement('div');
//     card.className = 'course-card neocard p-3';

//     const enrolled = course.enrolled || false;
//     const progress = localStorage.getItem(`progress_${course.id}`) || '0';

//     card.innerHTML = `
//       <h4>${course.name}</h4>
//       <p>${course.description}</p>
//       <p><strong>Rating:</strong> ${renderRatingStars(course.rating)}</p>
//       <p><strong>Progress:</strong> ${progress}%</p>
//       <button class="btn ${enrolled ? 'btn-secondary' : 'btn-primary'} enroll-btn" data-id="${course.id}" ${enrolled ? 'disabled' : ''}>
//         ${enrolled ? 'Enrolled' : 'Enroll'}
//       </button>
//     `;

//     const button = card.querySelector('button');
//     button.addEventListener('click', () => handleCourseAction(button, course.id));
//     courseCardsContainer.appendChild(card);
//   });
// }

// function sortCourses(courses) {
//   const sortBy = sortSelect.value;
//   return [...courses].sort((a, b) => {
//     if (sortBy === 'name') return a.name.localeCompare(b.name);
//     if (sortBy === 'rating') return b.rating - a.rating;
//     return 0;
//   });
// }

// function renderRatingStars(rating) {
//   const fullStars = Math.floor(rating);
//   const halfStar = rating % 1 >= 0.5 ? '★' : '';
//   const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
//   return '★'.repeat(fullStars) + halfStar + '☆'.repeat(emptyStars);
// }

// ===================== Course Enrollment =====================
// async function handleCourseAction(button, courseId) {
//   button.disabled = true;
//   button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enrolling';

//   try {
//     const response = await fetch('../backend/enroll.php', {
//       method: 'POST',
//       headers: { 'Content-Type': 'application/json' },
//       body: JSON.stringify({ username, token, course_id: courseId })
//     });

//     const result = await response.json();
//     if (result.success) {
//       button.classList.remove('btn-primary');
//       button.classList.add('btn-secondary');
//       button.innerHTML = 'Enrolled';
//     } else {
//       showAlert('danger', result.message);
//       button.disabled = false;
//       button.innerHTML = 'Enroll';
//     }
//   } catch (error) {
//     console.error('Enrollment error:', error);
//     showAlert('danger', 'Enrollment failed. Try again.');
//     button.disabled = false;
//     button.innerHTML = 'Enroll';
//   }
// }

// ===================== Utility =====================
// function showAlert(type, message) {
//   const alert = document.createElement('div');
//   alert.className = `alert alert-${type}`;
//   alert.textContent = message;
//   document.body.appendChild(alert);
//   setTimeout(() => alert.remove(), 3000);
// }

// // ===================== Event Listeners =====================
// profileIcon.addEventListener('click', () => {
//   dropdownMenu.classList.toggle('show');
// });

// document.getElementById('logout-btn').addEventListener('click', () => {
//   localStorage.clear();
//   window.location.href = '../index.html';
// });

// searchInput.addEventListener('input', renderCourses);
// sortSelect.addEventListener('change', renderCourses);

// // ===================== Initialize =====================
// loadDashboardData();
// {
    
// }
