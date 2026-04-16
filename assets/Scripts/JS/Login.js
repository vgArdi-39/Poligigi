function showNextStep() {
  const username = document.getElementById('username').value;

  if (username.trim() === "") {
    alert("Please enter a username!");
    return;
  }

  // Hide Step 1, Show Step 2
  document.getElementById('step-username').classList.add('hidden');
  document.getElementById('step-password').classList.remove('hidden');
}

function showPrevStep() {
  // Hide Step 2, Show Step 1
  document.getElementById('step-password').classList.add('hidden');
  document.getElementById('step-username').classList.remove('hidden');
}