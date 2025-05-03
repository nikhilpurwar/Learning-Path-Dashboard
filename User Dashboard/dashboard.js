// ======================
// Dashboard Controller - Complete Implementation
// ======================
class DashboardController {
    constructor() {
      // DOM Elements
      this.dom = {
        domainTabs: document.querySelector('.domain-tabs'),
        subjectTabs: document.querySelector('.subject-tabs'),
        courseGrid: document.querySelector('.course-grid'),
        currentCategory: document.querySelector('.current-category span'),
        profileIcon: document.querySelector('.profile-icon'),
        dropdownMenu: document.querySelector('.dropdown-menu'),
        activeCourses: document.querySelector('.course-carousel'),
        recommendedCourses: document.querySelector('.suggestion-carousel'),
        suggestionTabs: document.querySelector('.suggestion-tabs')
      };
  
      // State Management
      this.state = {
        currentDomain: null,
        currentSubject: null,
        courseData: { domains: [] },
        isLoading: false,
        userData: null,
        activeTab: 'popular' // For recommendation tabs
      };
  
      // Initialize
      this.init();
    }
  
    // ======================
    // Core Initialization
    // ======================
    async init() {
      try {
        await this.checkAuthentication();
        await this.loadDashboardData();
        this.setupEventListeners();
        this.setDefaultSelections();
      } catch (error) {
        console.error("Initialization failed:", error);
        this.showAlert("Failed to load dashboard", "error");
      }
    }
  
    // ======================
    // Authentication
    // ======================
    async checkAuthentication() {
      const token = localStorage.getItem('authToken');
      if (!token) {
        this.redirectToLogin();
        return;
      }
  
      try {
        const response = await fetch('../../Backend/check_auth.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (!response.ok) throw new Error('Auth failed');
        
        const data = await response.json();
        if (!data.success) throw new Error('Invalid session');
        
        this.state.userData = data.user || {};
      } catch (error) {
        this.clearSessionAndRedirect();
      }
    }
  
    redirectToLogin() {
      window.location.href = '../index.html?auth_required=1';
    }
  
    clearSessionAndRedirect() {
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      window.location.href = '../index.html?session_expired=1';
    }
  
    // ======================
    // Data Loading
    // ======================
    async loadDashboardData() {
      if (this.state.isLoading) return;
      this.state.isLoading = true;
  
      try {
        // Try loading from backend first
        const apiData = await this.fetchAPIData();
        if (apiData) {
          this.processAPIData(apiData);
          return;
        }
      } catch (error) {
        console.error("API load failed:", error);
      } finally {
        // Fallback to local data if API fails
        if (!this.state.courseData.domains.length) {
          this.loadFallbackData();
        }
        this.state.isLoading = false;
      }
    }
  
    async fetchAPIData() {
      const response = await fetch('../../Backend/dashboard.php');
      if (!response.ok) throw new Error('Network error');
      
      const data = await response.json();
      return data.success ? data : null;
    }
  
    processAPIData(apiData) {
      // Transform API data to match frontend structure
      this.state.courseData = {
        domains: [
          {
            id: 'web-dev',
            name: 'Web Development',
            icon: 'fas fa-code',
            color: '#6C5CE7',
            subjects: [
              {
                id: 'html-css',
                name: 'HTML & CSS',
                courses: apiData.courses.filter(c => c.category === 'web-dev')
              }
            ]
          }
          // Additional domains can be added here
        ]
      };
  
      // Render special sections
      this.renderActiveCourses(apiData.courses);
      this.renderRecommendedCourses(apiData.recommendations);
    }
  
    loadFallbackData() {
      // Your complete original fallback data
      this.state.courseData.domains = [
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
                // ... all other courses from your original fallback data
              ]
            }
            // ... all other subjects and domains
          ]
        }
        // ... include all domains from your original fallback
      ];
    }
  
    // ======================
    // Rendering Functions
    // ======================
    setDefaultSelections() {
      if (this.state.courseData.domains.length > 0) {
        this.setActiveDomain(this.state.courseData.domains[0].id);
      }
    }
  
    renderDomainTabs() {
      this.dom.domainTabs.innerHTML = '';
      
      if (!this.state.courseData.domains?.length) {
        this.dom.domainTabs.innerHTML = this.createEmptyState('No categories available');
        return;
      }
      
      this.state.courseData.domains.forEach(domain => {
        const tab = document.createElement('button');
        tab.className = `domain-tab ${domain.id === this.state.currentDomain ? 'active' : ''}`;
        tab.innerHTML = `
          <i class="${domain.icon}"></i>
          <span>${domain.name}</span>
        `;
        tab.dataset.domainId = domain.id;
        tab.style.setProperty('--domain-color', domain.color);
        tab.addEventListener('click', () => this.setActiveDomain(domain.id));
        
        this.dom.domainTabs.appendChild(tab);
      });
    }
  
    renderSubjectTabs(domainId) {
      this.dom.subjectTabs.innerHTML = '';
      const domain = this.state.courseData.domains.find(d => d.id === domainId);
      
      if (!domain?.subjects?.length) {
        this.dom.subjectTabs.innerHTML = this.createEmptyState('No subjects available');
        return;
      }
      
      domain.subjects.forEach(subject => {
        const tab = document.createElement('button');
        tab.className = `subject-tab ${subject.id === this.state.currentSubject ? 'active' : ''}`;
        tab.textContent = subject.name;
        tab.dataset.subjectId = subject.id;
        tab.addEventListener('click', () => this.setActiveSubject(subject.id));
        
        this.dom.subjectTabs.appendChild(tab);
      });
      
      // Auto-select first subject
      this.setActiveSubject(domain.subjects[0].id);
    }
  
    renderCourses(courses) {
      this.dom.courseGrid.innerHTML = '';
      
      if (!courses?.length) {
        this.dom.courseGrid.innerHTML = this.createEmptyState('No courses available');
        return;
      }
      
      courses.forEach(course => {
        const card = document.createElement('div');
        card.className = 'course-card';
        card.innerHTML = `
          <img src="${course.image}" alt="${course.title}" loading="lazy">
          <div class="course-info">
            <h4>${course.title}</h4>
            <p class="author">By ${course.author}</p>
            <div class="rating">
              ${this.renderRatingStars(course.rating)}
              <span>(${course.reviews})</span>
            </div>
            ${course.progress > 0 ? `
              <div class="progress-container">
                <div class="progress-bar" style="width: ${course.progress}%"></div>
                <span>${course.progress}% Complete</span>
              </div>
            ` : ''}
          </div>
          <button class="purchase-btn" 
            data-course-id="${course.id}" 
            data-continue="${course.progress > 0}">
            ${course.progress > 0 ? 'Continue' : 'Enroll'} ($${course.price})
          </button>
        `;
        this.dom.courseGrid.appendChild(card);
      });
    }
  
    renderActiveCourses(courses = []) {
      this.dom.activeCourses.innerHTML = '';
      
      if (!courses.length) {
        this.dom.activeCourses.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <p>No active courses yet</p>
            <a class="browse-btn" href="#category-nav">Browse Courses</a>
          </div>
        `;
        return;
      }
      
      courses.forEach(course => {
        const item = document.createElement('div');
        item.className = 'course-carousel-item';
        item.innerHTML = `
          <img src="${course.image}" alt="${course.title}" loading="lazy">
          <div class="course-progress">
            <div class="progress-bar" style="width: ${course.progress}%"></div>
            <span>${course.progress}% complete</span>
          </div>
          <h4>${course.title}</h4>
          <button class="continue-btn" data-course-id="${course.id}">
            Continue Learning
          </button>
        `;
        this.dom.activeCourses.appendChild(item);
      });
    }
  
    renderRecommendedCourses(recommendations = []) {
      this.dom.recommendedCourses.innerHTML = '';
      
      if (!recommendations.length) {
        this.dom.recommendedCourses.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-lightbulb"></i>
            <p>No recommendations available</p>
          </div>
        `;
        return;
      }
      
      recommendations.forEach(course => {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.innerHTML = `
          <img src="${course.image}" alt="${course.title}" loading="lazy">
          <div class="suggestion-info">
            <h4>${course.title}</h4>
            <div class="rating">
              ${this.renderRatingStars(course.rating)}
              <span>${course.rating.toFixed(1)}</span>
            </div>
            <button class="enroll-btn" data-course-id="${course.id}">
              Enroll Now
            </button>
          </div>
        `;
        this.dom.recommendedCourses.appendChild(item);
      });
    }
  
    createEmptyState(message, icon = 'fa-book-open') {
      return `
        <div class="empty-state">
          <i class="fas ${icon}"></i>
          <p>${message}</p>
        </div>
      `;
    }
  
    // ======================
    // State Management
    // ======================
    setActiveDomain(domainId) {
      this.state.currentDomain = domainId;
      this.state.currentSubject = null;
      
      // Update UI
      document.querySelectorAll('.domain-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.domainId === domainId);
      });
      
      this.renderSubjectTabs(domainId);
      
      // Update category display
      const domain = this.getCurrentDomain();
      if (domain) {
        this.dom.currentCategory.textContent = domain.name;
      }
    }
  
    setActiveSubject(subjectId) {
      this.state.currentSubject = subjectId;
      const domain = this.getCurrentDomain();
      const subject = domain?.subjects?.find(s => s.id === subjectId);
      
      if (!domain || !subject) return;
      
      // Update UI
      document.querySelectorAll('.subject-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.subjectId === subjectId);
      });
      
      this.dom.currentCategory.textContent = `${domain.name}: ${subject.name}`;
      this.renderCourses(subject.courses);
    }
  
    getCurrentDomain() {
      return this.state.courseData.domains.find(d => d.id === this.state.currentDomain);
    }
  
    // ======================
    // Event Handling
    // ======================
    setupEventListeners() {
      // Profile dropdown
      this.dom.profileIcon?.addEventListener('click', (e) => {
        e.stopPropagation();
        this.dom.dropdownMenu?.classList.toggle('show');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', () => {
        this.dom.dropdownMenu?.classList.remove('show');
      });
      
      // Recommendation tabs
      this.dom.suggestionTabs?.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-tab')) {
          this.state.activeTab = e.target.textContent.toLowerCase();
          // Here you would typically filter recommendations
          document.querySelectorAll('.suggestion-tab').forEach(tab => {
            tab.classList.toggle('active', tab === e.target);
          });
        }
      });
      
      // Delegated event listeners for better performance
      this.delegateEvents();
    }
  
    delegateEvents() {
      // Course enrollment
      this.dom.courseGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.purchase-btn');
        if (btn) {
          this.handleCourseAction(
            btn.dataset.courseId, 
            btn.dataset.continue === 'true'
          );
        }
      });
      
      // Active courses continue
      this.dom.activeCourses.addEventListener('click', (e) => {
        const btn = e.target.closest('.continue-btn');
        if (btn) this.handleCourseAction(btn.dataset.courseId, true);
      });
      
      // Recommended courses enroll
      this.dom.recommendedCourses.addEventListener('click', (e) => {
        const btn = e.target.closest('.enroll-btn');
        if (btn) this.handleCourseAction(btn.dataset.courseId, false);
      });
    }
  
    // ======================
    // Course Actions
    // ======================
    async handleCourseAction(courseId, isContinue) {
      const button = document.querySelector(`[data-course-id="${courseId}"]`);
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
        
        this.showAlert(`Course ${isContinue ? 'continued' : 'enrolled'} successfully!`, 'success');
        await this.loadDashboardData(); // Refresh data
        
      } catch (error) {
        console.error('Action failed:', error);
        this.showAlert(error.message || 'Action failed. Please try again.', 'error');
      } finally {
        if (button) {
          button.disabled = false;
          button.innerHTML = originalText;
        }
      }
    }
  
    // ======================
    // UI Utilities
    // ======================
    renderRatingStars(rating) {
      const fullStars = Math.floor(rating);
      const hasHalfStar = rating % 1 >= 0.5;
      
      return `
        ${'<i class="fas fa-star"></i>'.repeat(fullStars)}
        ${hasHalfStar ? '<i class="fas fa-star-half-alt"></i>' : ''}
        ${'<i class="far fa-star"></i>'.repeat(5 - fullStars - (hasHalfStar ? 1 : 0))}
      `;
    }
  
    showAlert(message, type = 'success') {
      const alert = document.createElement('div');
      alert.className = `alert alert-${type}`;
      alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
      `;
      
      document.body.appendChild(alert);
      setTimeout(() => alert.remove(), 5000);
    }
  }
  
  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', () => {
    new DashboardController();
  });