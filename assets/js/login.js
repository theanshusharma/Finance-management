const container = document.querySelector('.container');
const registerBtn = document.getElementById('register');
const loginBtn = document.getElementById('login');

registerBtn.addEventListener('click', () => {
    container.classList.add('active');
});

loginBtn.addEventListener('click', () => {
    container.classList.remove('active');
});

const form = document.querySelector("form");

form.addEventListener("submit", (e) => {
    e.preventDefault();
    form.submit();
});