/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/
let weeks = [];

const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');

function createWeekRow(week) {
  const tr = document.createElement('tr');
  
  const tdTitle = document.createElement('td');
  tdTitle.textContent = week.title;
  tr.appendChild(tdTitle);
  
  const tdStartDate = document.createElement('td');
  tdStartDate.textContent = week.start_date;
  tr.appendChild(tdStartDate);
  
  const tdDescription = document.createElement('td');
  tdDescription.textContent = week.description.substring(0, 100) + (week.description.length > 100 ? '...' : '');
  tr.appendChild(tdDescription);
  
  const tdActions = document.createElement('td');
  tdActions.className = 'action-buttons';
  
  const viewBtn = document.createElement('a');
  viewBtn.href = `details.html?id=${week.id}`;
  viewBtn.className = 'view-btn';
  viewBtn.textContent = 'View';
  tdActions.appendChild(viewBtn);
  
  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'delete-btn';
  deleteBtn.textContent = 'Delete';
  deleteBtn.dataset.id = week.id;
  tdActions.appendChild(deleteBtn);
  
  tr.appendChild(tdActions);
  
  return tr;
}

function renderTable() {
  if (!weeksTableBody) return;
  
  weeksTableBody.innerHTML = '';
  
  if (weeks.length === 0) {
    weeksTableBody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No weeks available. Add your first week above!</td></tr>';
    return;
  }
  
  weeks.forEach(week => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

async function handleAddWeek(event) {
  event.preventDefault();
  
  const title = document.querySelector('#week-title').value.trim();
  const startDate = document.querySelector('#week-start-date').value;
  const description = document.querySelector('#week-description').value.trim();
  const linksText = document.querySelector('#week-links').value.trim();
  
  const links = linksText ? linksText.split('\n').map(link => link.trim()).filter(link => link) : [];
  
  try {
    const response = await fetch('api/index.php?resource=weeks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: title,
        start_date: startDate,
        description: description,
        links: links
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      weeks.push(result.data);
      renderTable();
      weekForm.reset();
      alert('Week added successfully!');
    } else {
      alert('Error adding week: ' + result.error);
    }
  } catch (error) {
    console.error('Error adding week:', error);
    alert('Error adding week. Please try again.');
  }
}

async function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const weekId = event.target.dataset.id;
    
    if (!confirm('Are you sure you want to delete this week?')) {
      return;
    }
    
    try {
      const response = await fetch(`api/index.php?resource=weeks&id=${weekId}`, {
        method: 'DELETE'
      });
      
      const result = await response.json();
      
      if (result.success) {
        weeks = weeks.filter(w => w.id != weekId);
        renderTable();
        alert('Week deleted successfully!');
      } else {
        alert('Error deleting week: ' + result.error);
      }
    } catch (error) {
      console.error('Error deleting week:', error);
      alert('Error deleting week. Please try again.');
    }
  }
}

async function loadAndInitialize() {
  try {
    const response = await fetch('api/index.php?resource=weeks');
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Get the raw text first to debug
    const text = await response.text();
    console.log('API Response:', text);
    
    // Try to parse as JSON
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('Failed to parse JSON:', text);
      throw new Error('Invalid JSON response from server');
    }
    
    if (result.success && result.data) {
      weeks = result.data;
      renderTable();
    } else if (result.error) {
      console.error('API Error:', result.error);
      if (weeksTableBody) {
        weeksTableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: red;">Error: ${result.error}</td></tr>`;
      }
    } else {
      weeks = [];
      renderTable();
    }
    
    if (weekForm) {
      weekForm.addEventListener('submit', handleAddWeek);
    }
    
    if (weeksTableBody) {
      weeksTableBody.addEventListener('click', handleTableClick);
    }
  } catch (error) {
    console.error('Error loading weeks:', error);
    if (weeksTableBody) {
      weeksTableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: red;">Error: ${error.message}. Check console for details.</td></tr>`;
    }
  }
}

if (weeksTableBody) {
  loadAndInitialize();
}
