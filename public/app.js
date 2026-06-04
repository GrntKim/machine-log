const button = document.querySelector('#ping-button');
const signal = document.querySelector('#signal');

button?.addEventListener('click', () => {
    const now = new Date().toLocaleString();
    signal.textContent = `Signal received at ${now}`;
});
