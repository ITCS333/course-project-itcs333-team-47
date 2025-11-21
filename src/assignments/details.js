/* Assignment Details Page Logic */

// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");

const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---

// Get assignment ID from URL (?id=asg_1)
function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}


// Render assignment information
function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = "Due: " + assignment.dueDate;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = "";

  assignment.files.forEach(file => {
    const li = document.createElement("li");
    li.innerHTML = `<a href="#">${file}</a>`;
    assignmentFilesList.appendChild(li);
  });
}


// Create HTML <article> for a single comment
function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.classList.add("comment");

  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;

  return article;
}


// Render all comments
function renderComments() {
  commentList.innerHTML = "";

  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}


// Add new comment
function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (text === "") return;

  const newComment = {
    author: "Student", // fixed for this assignment
    text
  };

  currentComments.push(newComment);
  renderComments();

  newCommentText.value = "";
}


// Initialize page (main function)
async function initializePage() {
  // 1. Get ID from URL
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = "Error: No assignment ID provided in URL.";
    return;
  }

  try {
    // 2. Load assignments + comments
    const [assignmentsResponse, commentsResponse] = await Promise.all([
      fetch("assignments.json"),
      fetch("comments.json")
    ]);

    const assignments = await assignmentsResponse.json();
    const comments = await commentsResponse.json();

    // 3. Find assignment object
    const assignment = assignments.find(a => a.id === currentAssignmentId);

    // 4. Load comments for this assignment
    currentComments = comments[currentAssignmentId] || [];

    // 5. If assignment exists → render
    if (assignment) {
      renderAssignmentDetails(assignment);
      renderComments();

      // Enable adding comments
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      assignmentTitle.textContent = "Error: Assignment not found.";
    }
  } catch (error) {
    assignmentTitle.textContent = "Error loading data.";
    console.error(error);
  }
}


// --- Start the Page ---
initializePage();
