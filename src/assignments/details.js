/*
  Assignment Details Page Logic
*/

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

function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

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

function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.classList.add("comment");
  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;
  return article;
}

function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (text === "") return;

  const newComment = { author: "Student", text };
  currentComments.push(newComment);

  renderComments();
  newCommentText.value = "";
}

async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = "Error: No assignment ID in URL.";
    return;
  }

  try {
    const [assignmentsRes, commentsRes] = await Promise.all([
      fetch("api/assignments.json"),   // FIXED
      fetch("api/comments.json")       // FIXED
    ]);

    const assignments = await assignmentsRes.json();
    const comments = await commentsRes.json();

    const assignment = assignments.find(a => a.id === currentAssignmentId);
    currentComments = comments[currentAssignmentId] || [];

    if (assignment) {
      renderAssignmentDetails(assignment);
      renderComments();
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      assignmentTitle.textContent = "Assignment not found.";
    }

  } catch (err) {
    assignmentTitle.textContent = "Error loading assignment.";
    console.error(err);
  }
}

initializePage();
