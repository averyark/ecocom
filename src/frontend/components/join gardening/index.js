  document.getElementById('joinButton').addEventListener('click', function() {
    // Hide the join button
    this.style.display = 'none';
        
    // Show the unjoin and view garden buttons
    document.getElementById('postJoinButtons').style.display = 'flex';
});
    
// You might want to add functionality for the unjoin button to reverse this process
document.querySelector('.unjoin-garden').addEventListener('click', function() {
    document.getElementById('joinButton').style.display = 'block';
    document.getElementById('postJoinButtons').style.display = 'none';
});
