/*
  Course Assignments - List Page Logic
*/

// --- Element Selections ---
const listSection = document.getElementById("assignment-list-section");

// --- Functions ---

// Create <article> for a single assignment
function createAssignmentArticle(assignment) {
  const article = document.createElement("article");
  article.classList.add("assignment-card"); // مهم
  article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p><strong>Due:</strong> ${assignment.dueDate}</p>
    <p>${assignment.description}</p>
    <a href="details.html?id=${assignment.id}">View Details & Discussion</a>
  `;
  return article;
}


// Load assignments.json and display them
async function loadAssignments() {
  try {
    const response = await fetch("api/assignments.json"); // FIXED
    const assignments = await response.json();

    // Clear section
    listSection.innerHTML = "";

    assignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error("Error loading assignments:", error);
    listSection.innerHTML = "<p>Error loading assignments.</p>";
  }
}

// --- Initial Page Load ---
loadAssignments();
