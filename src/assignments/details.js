/* Assignment Details Page Logic (PHP + MySQL) */

// --- Global ---
let currentAssignmentId = null;

// --- Element Selections ---
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");

const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// ----------------------
// Helpers
// ----------------------

function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;

  assignmentDueDate.innerHTML = `<strong>Due:</strong> ${assignment.due_date}`;

  assignmentDescription.textContent = assignment.description;

  
  assignmentFilesList.innerHTML = "";

  if (Array.isArray(assignment.files) && assignment.files.length > 0) {
    assignment.files.forEach((fileUrl) => {
      const li = document.createElement("li");

      
      const fileName = fileUrl.split("/").pop();

      li.innerHTML = `<a href="${fileUrl}" target="_blank" rel="noopener noreferrer">${fileName}</a>`;
      assignmentFilesList.appendChild(li);
    });
  } else {
    const li = document.createElement("li");
    li.textContent = "No files attached.";
    assignmentFilesList.appendChild(li);
  }
}


function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (text === "") return;

  const article = document.createElement("article");
  article.classList.add("comment");
  article.innerHTML = `
    <p>${text}</p>
    <footer>Posted by: Student</footer>
  `;

  commentList.appendChild(article);
  newCommentText.value = "";
}


async function initializePage() {
  
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = "Error: No assignment ID provided in URL.";
    return;
  }

  try {
   
    const response = await fetch(
      `api/index.php?resource=assignments&id=${encodeURIComponent(currentAssignmentId)}`
    );

    const json = await response.json();

    if (!json.success || !json.data) {
      assignmentTitle.textContent = "Error: Assignment not found.";
      return;
    }

    const assignment = json.data;

    
    renderAssignmentDetails(assignment);

    if (commentForm) {
      commentForm.addEventListener("submit", handleAddComment);
    }
  } catch (error) {
    console.error("Error loading assignment:", error);
    assignmentTitle.textContent = "Error loading data.";
  }
}

// --- Start ---
initializePage();
