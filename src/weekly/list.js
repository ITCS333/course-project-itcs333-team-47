const listSection = document.querySelector('#week-list-section');

function createWeekArticle(week) {
  const article = document.createElement('article');
  article.className = 'week-card';

  const h2 = document.createElement('h2');
  h2.textContent = week.title;
  article.appendChild(h2);

  const pStartDate = document.createElement('p');
  pStartDate.className = 'start-date';
  pStartDate.textContent = `Starts on: ${week.start_date}`;
  article.appendChild(pStartDate);

  const pDescription = document.createElement('p');
  pDescription.textContent = week.description;
  article.appendChild(pDescription);

  const a = document.createElement('a');
  a.href = `details.html?id=${week.id}`;
  a.className = 'details-link';
  a.textContent = 'View Details & Discussion';
  article.appendChild(a);

  return article;
}

async function loadWeeks() {
  try {
    const response = await fetch('api/index.php?resource=weeks');
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const text = await response.text();
    console.log('API Response:', text);
    
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('Failed to parse JSON:', text);
      throw new Error('Invalid JSON response from server');
    }
    
    if (result.success && result.data) {
      listSection.innerHTML = '';
      if (result.data.length === 0) {
        listSection.innerHTML = '<p>No weeks available yet.</p>';
      } else {
        result.data.forEach(week => {
          const article = createWeekArticle(week);
          listSection.appendChild(article);
        });
      }
    } else if (result.error) {
      listSection.innerHTML = `<p style="color: red;">Error: ${result.error}</p>`;
    } else {
      listSection.innerHTML = '<p>No weeks available.</p>';
    }
  } catch (error) {
    console.error('Error loading weeks:', error);
    listSection.innerHTML = `<p style="color: red;">Error: ${error.message}. Check console for details.</p>`;
  }
}

if (listSection) {
  loadWeeks();
}
