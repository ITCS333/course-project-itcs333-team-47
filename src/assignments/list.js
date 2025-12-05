/*
  Course Assignments - List Page Logic (Phase 3 - PHP + MySQL)
*/

// --- Element Selections ---
const listSection = document.getElementById("assignment-list-section");

// --- Functions ---

// Create <article> for a single assignment
function createAssignmentArticle(assignment) {
  const article = document.createElement("article");

  article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p><strong>Due:</strong> ${assignment.due_date}</p>
    <p>${assignment.description}</p>
    <a href="details.html?id=${assignment.id}">View Details & Discussion</a>
  `;

  return article;
}

// Load assignments from PHP API
async function loadAssignments() {
  try {
    const response = await fetch("api/index.php?resource=assignments");
    const json = await response.json();

    const assignments = json.data || [];

    // Clear section
    listSection.innerHTML = "";

    if (assignments.length === 0) {
      listSection.innerHTML = "<p>No assignments found.</p>";
      return;
    }

    // Add each assignment
    assignments.forEach((assignment) => {
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
