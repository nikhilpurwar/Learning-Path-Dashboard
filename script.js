window.onload = () => {
    window.scrollTo(0, 0); // Scroll to the top-left corner of the page
};
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.classList.add('show');
    }, 100); // slight delay to trigger transition
  
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => {
        toast.remove();
      }, 500); // wait for hide transition to complete
    }, 3000); // visible for 3 seconds
  }
// ======================
// Utility Functions
// ======================
function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(input);
}

function validatePhoneNumber(input) {
    const phoneRegex = /^\d{10}$/;
    return phoneRegex.test(input);
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fixed-top mx-auto mt-3`;
    alertDiv.style.maxWidth = '500px';
    alertDiv.style.zIndex = '2000';
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.classList.add('fade');
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// ======================
// Modal Functions
// ======================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "flex";
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll(".modal");
    modals.forEach((modal) => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
};

// ======================
// Auth Functions
// ======================
async function handleRegister(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!form.username.value || !form.email.value || !form.phone.value || !form.password.value) {
        showAlert('All fields are required', 'error');
        return;
    }

    if (!validateEmail(form.email.value)) {
        showAlert('Please enter a valid email address', 'error');
        return;
    }

    if (!validatePhoneNumber(form.phone.value)) {
        showAlert('Please enter a 10-digit phone number', 'error');
        return;
    }

    if (form.password.value.length < 8) {
        showAlert('Password must be at least 8 characters', 'error');
        return;
    }

    if (form.password.value !== form.confirmPassword.value) {
        showAlert('Passwords do not match', 'error');
        return;
    }

    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Signing Up...';

        const formData = new FormData(form);
        const plainFormData = Object.fromEntries(formData.entries());

        const response = await fetch('./Backend/signup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' ,
                        'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify(plainFormData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            throw new Error(errorData?.message || 'Registration failed');
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Registration failed');
        }

        showAlert('Registration successful! Redirecting...', 'success');

        // Redirect to index.html after a short delay
        setTimeout(() => {
            closeModal('signupModal');
            openModal('loginModal');
        }, 1000);

    } catch (error) {
        console.error('Registration error:', error);
        showAlert(error.message || 'Registration failed. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign Up';
    }
}


async function handleLogin(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Logging in...';

        const formData = {
            loginInput: form.loginInput.value,
            password: form.loginPassword.value
        };

        console.log('Submitting login:', formData);

        const response = await fetch('./Backend/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        console.log('Login response status:', response.status);

        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            if(errorData?.message === "Incorrect password")
            {
                showToast(`Wrong Password`);
            }
            else if(errorData?.message === "User not found")
            {
                showToast(`Invalid User ID`);
            }
            throw new Error(errorData?.message || 'Login failed');
            
        }

        const data = await response.json();
        console.log('Login success:', data);

        if (!data.success) {
            throw new Error(data.message || 'Login failed');
        }

        // Store token and user data
        localStorage.setItem('authToken', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        showAlert('Login successful! Redirecting...', 'success');
        
        // Redirect after short delay
        setTimeout(() => {
            window.location.href = './User Dashboard/dashboard.php';
        }, 1500);

    } catch (error) {
        console.error('Login error:', error);
        showAlert(error.message || 'Login failed. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
    }
}

// ======================
// Event Listeners
// ======================
document.addEventListener('DOMContentLoaded', function() {
    // Clear any existing session data
    sessionStorage.clear();

    // Login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Signup form submission
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', handleRegister);
    }

    // Modal open/close handlers
    document.querySelectorAll('[data-modal-open]').forEach(button => {
        button.addEventListener('click', () => {
            openModal(button.dataset.modalOpen);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(button => {
        button.addEventListener('click', () => {
            closeModal(button.dataset.modalClose);
        });
    });
});
document.addEventListener("DOMContentLoaded", () => {
    const carousel = document.querySelector(".testimonial-carousel");

    // Automatically scroll to the center card on reload
    const scrollToCenterCard = () => {
        const carouselChildren = Array.from(carousel.children);
        const centerCardIndex = Math.floor(carouselChildren.length / 2); // Calculate the index of the center card
        const centerCard = carouselChildren[centerCardIndex];
        centerCard.scrollIntoView({
            behavior: "instant", // Instantly scroll to center card on page load
            block: "nearest",
            inline: "center"
        });
    };

    scrollToCenterCard(); // Call the function when DOM is loaded

    // Smooth snapping to center
    carousel.addEventListener("scroll", () => {
        let activeCard = null;
        const carouselRect = carousel.getBoundingClientRect();

        // Debounce logic for scroll snapping
        let isScrolling = false;
        if (!isScrolling) {
            isScrolling = true;
            setTimeout(() => {
                isScrolling = false;

                // Find the card closest to the center
                Array.from(carousel.children).forEach(card => {
                    const cardRect = card.getBoundingClientRect();
                    const cardCenter = cardRect.left + cardRect.width / 2;
                    const carouselCenter = carouselRect.left + carouselRect.width / 2;

                    if (!activeCard || Math.abs(carouselCenter - cardCenter) < Math.abs(carouselCenter - activeCard.center)) {
                        activeCard = { card, center: cardCenter };
                    }
                });

                // Scroll to the active card
                if (activeCard) {
                    activeCard.card.scrollIntoView({
                        behavior: "smooth",
                        block: "nearest",
                        inline: "center"
                    });
                }
            }, 100); // Adjust debounce timing if needed
        }
    });

    // Touch functionality for mobile
    let xStart = null;

    carousel.addEventListener("touchstart", handleTouchStart, false);
    carousel.addEventListener("touchmove", handleTouchMove, false);

    function handleTouchStart(evt) {
        const touch = evt.touches[0];
        xStart = touch.clientX;
    }

    function handleTouchMove(evt) {
        if (!xStart) return;

        const touch = evt.touches[0];
        const xDiff = xStart - touch.clientX;

        // Use requestAnimationFrame for smooth scrolling
        requestAnimationFrame(() => {
            carousel.scrollLeft += xDiff;
            xStart = touch.clientX;
        });
    }

    // Prevent overscroll at edges
    carousel.addEventListener("scroll", () => {
        const maxScroll = carousel.scrollWidth - carousel.clientWidth;
        if (carousel.scrollLeft < 0) {
            carousel.scrollLeft = 0;
        } else if (carousel.scrollLeft > maxScroll) {
            carousel.scrollLeft = maxScroll;
        }
    });
});



// Select all the nav links
const navLinks = document.querySelectorAll('.nav-links a');

// Add click event listener to each link
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        // Remove 'active' class from all links
        navLinks.forEach(nav => nav.classList.remove('active'));

        // Add 'active' class to the clicked link
        link.classList.add('active');
    });
});

// Track scroll behavior
let lastScrollY = window.scrollY; // Tracks the current vertical scroll position
const header = document.querySelector("header");

// Scroll behavior listener
window.addEventListener("scroll", () => {
    if (window.scrollY > lastScrollY) {
        // Scrolling down - hide header
        header.classList.add("hide");
    } else {
        // Scrolling up - show header
        header.classList.remove("hide");
    }
    lastScrollY = window.scrollY; // Update the last scroll position
});

// Select elements
const hamburger = document.getElementById("hamburger");
const navLink = document.getElementById("nav-links");

// Add click event listener
hamburger.addEventListener("click", () => {
    navLink.classList.toggle("active"); // Show/hide menu
    hamburger.classList.toggle("open"); // Optionally animate the hamburger icon
});

document.addEventListener('DOMContentLoaded', function() {
    const viewAllBtn = document.getElementById('view-all-btn');
    const hiddenCourses = document.querySelectorAll('.hidden-course');
    const featuredSection = document.getElementById('featured-courses');
    let showingAll = false;
    
    viewAllBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default button behavior
        
        showingAll = !showingAll;
        
        hiddenCourses.forEach(course => {
            course.style.display = showingAll ? 'flex' : 'none';
        });
        
        viewAllBtn.textContent = showingAll ? 'Show Less' : 'View All Courses';
        
        if (!showingAll) {
            featuredSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
});
// function validateEmail(input) {
//     const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//     return emailRegex.test(input);
// }

// function validatePhoneNumber(input) {
//     const phoneRegex = /^\d{10}$/;
//     return phoneRegex.test(input);
// }

// // Login/Signup Backend Integration with PHP
// // async function handleRegister(event) {
// //     event.preventDefault();

// //     const formData = new FormData(event.target);
// //     const submitBtn = event.target.querySelector('button[type="submit"]');
    
    

// //     if (!formData.get('username').trim() || 
// //     !formData.get('email').trim() || 
// //     !formData.get('phone').trim() || 
// //     !formData.get('password').trim() || 
// //     !formData.get('confirmPassword').trim()) {
// //         showAlert('Please fill in all fields', 'error');
// //         return;
// //     }

// //     if (formData.get('password') !== formData.get('confirmPassword')) {
// //         showAlert('Passwords do not match!', 'error');
// //         return;
// //     }

// //     if (formData.get('password').length < 8) {
// //         showAlert('Password must be at least 8 characters long', 'error');
// //         return;
// //     }

// //     if (!validatePhoneNumber(formData.get('phone'))) {
// //         showAlert('Please enter a valid 10-digit phone number', 'error');
// //         return;
// //     }

// //     try {
// //         submitBtn.disabled = true;
// //         submitBtn.innerHTML = '<span class="spinner"></span> Registering...';

// //         const response = await fetch('Backend/signup.php', {
// //             method: 'POST',
// //             body: formData
// //         });
// //         const text = await response.text();
// //         if (text.trim() === "success") {
// //             showAlert('Registration successful!', 'success');
// //             closeModal('signupModal');
// //             await handleAutoLogin(formData.get('username'), formData.get('password'));
// //         } else {
// //             showAlert(text.replace("error:", "").trim(), 'error');
// //         }

        
// //         showAlert('Registration successful!', 'success');
// //         closeModal('signupModal');
// //         await handleAutoLogin(formData.get('username'), formData.get('password'));
// //     } catch (error) {
// //         showAlert(error.message, 'error');
// //     } finally {
// //         submitBtn.disabled = false;
// //         submitBtn.textContent = 'Sign Up';
// //     }
// // }


// async function handleRegister(event) {
//     event.preventDefault();
//     const form = event.target;
//     const submitBtn = form.querySelector('button[type="submit"]');
    
//     // Get form data
//     const formData = {
//         username: form.username.value,
//         email: form.email.value,
//         phone: form.phone.value,
//         password: form.password.value
//     };

//     try {
//         submitBtn.disabled = true;
//         submitBtn.innerHTML = '<span class="spinner"></span> Signing Up...';

//         const response = await fetch('Backend/signup.php', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//             },
//             body: JSON.stringify(formData)
//         });

//         const data = await response.json();

//         if (!data.success) {
//             throw new Error(data.message || 'Signup failed');
//         }

//         // Store token and redirect
//         localStorage.setItem('authToken', data.token);
//         window.location.href = 'User Dashboard/dashboard.html';

//     } catch (error) {
//         showAlert(error.message, 'error');
//     } finally {
//         submitBtn.disabled = false;
//         submitBtn.textContent = 'Sign Up';
//     }
// }

// // async function handleLogin(event) {
// //     event.preventDefault();

// //     const formData = new FormData(event.target);
// //     const submitBtn = event.target.querySelector('button[type="submit"]');

// //     if (!formData.get('loginInput') || !formData.get('password')) {
// //         showAlert('Please fill in all fields', 'error');
// //         return;
// //     }

// //     try {
// //         submitBtn.disabled = true;
// //         submitBtn.innerHTML = '<span class="spinner"></span> Logging in...';

// //         const response = await fetch('Backend/login.php', {
// //             method: 'POST',
// //             body: formData
// //         });

// //         const data = await response.json();

// //         if (!data.success) {
// //             showAlert(data.message || 'Login failed', 'error');
// //             return;
// //         }

// //         localStorage.setItem('authToken', data.token || ''); // If token exists
// //         localStorage.setItem('user', JSON.stringify(data.user));

// //         showAlert('Login successful!', 'success');
// //         closeModal('loginModal');
// //         window.location.href = 'User%20Dashboard/dashboard.html';

// //     } catch (error) {
// //         showAlert('Login error: ' + error.message, 'error');
// //     } finally {
// //         submitBtn.disabled = false;
// //         submitBtn.textContent = 'Login';
// //     }
// // }


// async function handleLogin(event) {
//     event.preventDefault();
//     const form = event.target;
//     const submitBtn = form.querySelector('button[type="submit"]');

//     try {
//         submitBtn.disabled = true;
//         submitBtn.innerHTML = '<span class="spinner"></span> Logging in...';

//         const response = await fetch('Backend/login.php', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//             },
//             body: JSON.stringify({
//                 loginInput: form.loginInput.value,
//                 password: form.loginPassword.value
//             })
//         });

//         const data = await response.json();

//         if (!data.success) {
//             throw new Error(data.message || 'Login failed');
//         }

//         // Store token and redirect
//         localStorage.setItem('authToken', data.token);
//         localStorage.setItem('user', JSON.stringify(data.user));
//         window.location.href = 'User Dashboard/dashboard.html';

//     } catch (error) {
//         showAlert(error.message, 'error');
//     } finally {
//         submitBtn.disabled = false;
//         submitBtn.textContent = 'Login';
//     }
// }

// async function handleAutoLogin(username, password) {
//     const formData = new FormData();
//     formData.append('loginInput', username);
//     formData.append('password', password);

//     try {
//         const response = await fetch('Backend/login.php', {
//             method: 'POST',
//             body: formData
//         });

//         const data = await response.json();
        
//         if (data.success) {
//             localStorage.setItem('authToken', data.token);
//             localStorage.setItem('user', JSON.stringify(data.user));
//             window.location.href = 'User%20Dashboard/dashboard.html';
//         }
//     } catch (error) {
//         console.error('Auto-login failed:', error);
//     }
// }

// function showAlert(message, type = 'success') {
//     const alertDiv = document.createElement('div');
//     alertDiv.className = `alert alert-${type} fixed-top mx-auto mt-3`;
//     alertDiv.style.maxWidth = '500px';
//     alertDiv.style.zIndex = '2000';
//     alertDiv.textContent = message;
    
//     document.body.appendChild(alertDiv);
    
//     setTimeout(() => {
//         alertDiv.classList.add('fade');
//         setTimeout(() => alertDiv.remove(), 300);
//     }, 3000);
// }

// // Function to open modal
// function openModal(modalId) {
//     const modal = document.getElementById(modalId);
//     if (modal) {
//         modal.style.display = "flex"; // Make it visible
//     }
// }

// // Function to close modal
// function closeModal(modalId) {
//     const modal = document.getElementById(modalId);
//     if (modal) {
//         modal.style.display = "none"; // Hide it
//     }
// }

// // Close modal when clicking outside of it
// window.onclick = function (event) {
//     const modals = document.querySelectorAll(".modal");
//     modals.forEach((modal) => {
//         if (event.target === modal) {
//             modal.style.display = "none";
//         }
//     });
// };

// document.addEventListener('DOMContentLoaded', function() {
//     sessionStorage.clear();
//     localStorage.clear();
// });
