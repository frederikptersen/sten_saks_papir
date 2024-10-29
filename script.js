function updateRanklist() {
    // Her kan du implementere AJAX-opdatering for at hente ny data
    document.getElementById('toast').style.display = 'block';
    setTimeout(() => document.getElementById('toast').style.display = 'none', 3000);
}
