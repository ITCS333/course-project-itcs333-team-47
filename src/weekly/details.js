let currentWeekId = null;
let currentComments = [];

const weekTitle = document.querySelector('#week-title');
const weekStartDate = document.querySelector('#week-start-date');
const weekDescription = document.querySelector('#week-description');
const weekLinksList = document.querySelector('#week-links-list');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentText = document.querySelector('#new-comment-text');

function getWeekIdFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('id');
}

function renderWeekDetails(week) {
  if (weekTitle) weekTitle.textContent = week.title;
  if (weekStartDate) weekStartDate.textContent = "Starts on: " + week.start_date;
  if (weekDescription) weekDescription.textContent = week.description;
  
  if (weekLinksList) {
    weekLinksList.innerHTML = '';
    const links = week.links || [];
    links.forEach(link => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = link;
      a.textContent = link;
      a.target = '_blank';
      li.appendChild(a);
      weekLinksList.appendChild(li);
    });
  }
}

function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.className = 'comment';
  
  const p = document.createElement('p');
  p.textContent = comment.text;
  article.appendChild(p);
  
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author} on ${comment.created_at}`;
  article.appendChild(footer);
  
  return article;
}

function renderComments() {
  if (!commentList) return;
  
  commentList.innerHTML = '';
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

async function handleAddComment(event) {
  event.preventDefault();
  
  const commentText = newCommentText.value.trim();
  if (!commentText) return;
  
  const author = document.querySelector('#author')?.value || 'Student';
  
  try {
    const response = await fetch('api/index.php?resource=comments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        week_id: currentWeekId,
        author: author,
        text: commentText
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      currentComments.push(result.data);
      renderComments();
      newCommentText.value = '';
    } else {
      alert('Error posting comment: ' + result.error);
    }
  } catch (error) {
    console.error('Error posting comment:', error);
    alert('Error posting comment. Please try again.');
  }
}

async function initializePage() {
  currentWeekId = getWeekIdFromURL();
  
  if (!currentWeekId) {
    if (weekTitle) weekTitle.textContent = "Week not found.";
    return;
  }

  try {
    const [weekResponse, commentsResponse] = await Promise.all([
      fetch(`api/index.php?resource=weeks&id=${currentWeekId}`),
      fetch(`api/index.php?resource=comments&week_id=${currentWeekId}`)
    ]);
    
    const weekResult = await weekResponse.json();
    const commentsResult = await commentsResponse.json();

    if (weekResult.success && weekResult.data) {
      renderWeekDetails(weekResult.data);
      
      if (commentsResult.success && commentsResult.data) {
        currentComments = commentsResult.data;
        renderComments();
      }
      
      if (commentForm) {
        commentForm.addEventListener('submit', handleAddComment);
      }
    } else {
      if (weekTitle) weekTitle.textContent = "Week not found.";
    }
  } catch (error) {
    console.error('Error loading data:', error);
    if (weekTitle) weekTitle.textContent = "Error loading week details.";
  }
}

if (weekTitle) {
  initializePage();
}
