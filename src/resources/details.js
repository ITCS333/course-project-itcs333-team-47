/*
  Requirement: Populate the resource detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="resource-title"`
     - To the description <p>: `id="resource-description"`
     - To the "Access Resource Material" <a> tag: `id="resource-link"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Leave a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment"`

  3. Implement the TODOs below.
*/ 

// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

// --- Functions ---

/**
 * Get the 'id' parameter from the URL
 */
function getResourceIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

/**
 * Render resource details
 */
function renderResourceDetails(resource) {
    resourceTitle.textContent = resource.title;
    resourceDescription.textContent = resource.description;
    resourceLink.href = resource.link;
}

/**
 * Create a comment <article> element
 */
function createCommentArticle(comment) {
    const article = document.createElement('article');
    article.classList.add('comment');

    const p = document.createElement('p');
    p.textContent = comment.text;

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${comment.author}`;

    article.appendChild(p);
    article.appendChild(footer);

    return article;
}

/**
 * Render the list of comments
 */
function renderComments() {
    commentList.innerHTML = '';
    currentComments.forEach(comment => {
        const article = createCommentArticle(comment);
        commentList.appendChild(article);
    });
}

/**
 * Handle Add Comment form submission
 */
function handleAddComment(event) {
    event.preventDefault();
    const commentText = newComment.value.trim();
    if (!commentText) return;

    const commentObj = {
        author: 'Student',
        text: commentText
    };

    currentComments.push(commentObj);
    renderComments();

    newComment.value = '';
}

/**
 * Initialize the page
 */
async function initializePage() {
    currentResourceId = getResourceIdFromURL();

    if (!currentResourceId) {
        resourceTitle.textContent = "Resource not found.";
        return;
    }

    try {
        const [resourcesRes, commentsRes] = await Promise.all([
            fetch('resources.json'),
            fetch('resource-comments.json')
        ]);

        const resources = await resourcesRes.json();
        const commentsData = await commentsRes.json();

        const resource = resources.find(r => r.id === currentResourceId);
        currentComments = commentsData[currentResourceId] || [];

        if (resource) {
            renderResourceDetails(resource);
            renderComments();
            commentForm.addEventListener('submit', handleAddComment);
        } else {
            resourceTitle.textContent = "Resource not found.";
        }
    } catch (error) {
        console.error('Error initializing page:', error);
        resourceTitle.textContent = "Failed to load resource.";
    }
}

// --- Initial Page Load ---
initializePage();
