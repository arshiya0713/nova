function scrollToSection(id) {
    document.getElementById(id).scrollIntoView({ behavior: 'smooth' });
}

let index = 0;
let track = null;
let total = 0;

function next() {
  if (!track) return;
  if (index === total - 1) {
    track.style.transition = "none";
    index = 0;
    track.style.transform = `translateX(0%)`;
    track.offsetHeight;
    track.style.transition = "transform 0.5s ease-in-out";
  } else {
    index++;
    track.style.transform = `translateX(-${index * 100}%)`;
  }
}

function prev() {
  if (!track) return;
  if (index === 0) {
    track.style.transition = "none";
    index = total - 1;
    track.style.transform = `translateX(-${index * 100}%)`;
    track.offsetHeight;
    track.style.transition = "transform 0.5s ease-in-out";
  } else {
    index--;
    track.style.transform = `translateX(-${index * 100}%)`;
  }
}

document.addEventListener("DOMContentLoaded", function() {
  track = document.getElementById("track");
  total = track ? track.children.length : 0;
  
  const form = document.getElementById("contactForm");
  const error = document.getElementById("error");

  if (form) {
    form.addEventListener("submit", function(e) {
      e.preventDefault();

      const name = document.getElementById("name").value.trim();
      const phone = document.getElementById("phone").value.trim();
      const email = document.getElementById("email").value.trim();
      const address = document.getElementById("address").value.trim();
      const message = document.getElementById("message").value.trim();

      if (!name || !phone || !email || !address || !message) {
        error.textContent = "All fields are required!";
        return;
      }

      if (!/^[a-zA-Z\s]+$/.test(name)) {
        error.textContent = "Name should only contain letters!";
        return;
      }

      if (!/^\d{10}$/.test(phone)) {
        error.textContent = "Phone number should be 10 digits!";
        return;
      }

      if (!email.includes("@")) {
        error.textContent = "Enter a valid email!";
        return;
      }

      const formData = new FormData();
      formData.append('name', name);
      formData.append('phone', phone);
      formData.append('email', email);
      formData.append('address', address);
      formData.append('message', message);

      fetch('contact_handler_debug.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        console.log("Response from server:", data);
        if (data.success) {
          error.style.color = "lightgreen";
          error.textContent = "Message recorded! Your Ticket ID: " + data.ticket_id;
          form.reset();
          setTimeout(() => {
            error.textContent = "";
            error.style.color = "#ff6b9d";
          }, 5000);
        } else {
          error.textContent = data.message || "Error!";
        }
      })
      .catch(err => {
        console.log("Error:", err);
        error.textContent = "Error connecting!";
      });
    });
  }
});