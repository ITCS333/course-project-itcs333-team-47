/*
  Requirement: Make the "Manage Assignments" page interactive.
*/

// --- Global Data Store ---
let assignments = [];

// --- Element Selections ---
const assignmentForm = document.getElementById("assignment-form");
const assignmentsTableBody = document.getElementById("assignments-tbody");


// --- Functions ---

// Create a table row <tr> for one assignment
function createAssignmentRow(assignment) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.dueDate}</td>
    <td>
      <button class="edit-btn" data-id="${assignment.id}">Edit</button>
      <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
  `;

  return tr;
}


// Render the full assignments table
function renderTable() {
  assignmentsTableBody.innerHTML = "";

  assignments.forEach((assignment) => {
    const row = createAssignmentRow(assignment);
    assignmentsTableBody.appendChild(row);
  });
}


// Handle adding a new assignment
function handleAddAssignment(event) {
  event.preventDefault();

  const title = document.getElementById("assignment-title").value;
  const description = document.getElementById("assignment-description").value;
  const dueDate = document.getElementById("assignment-due-date").value;
  const filesText = document.getElementById("assignment-files").value;

  const files = filesText
    .split("\n")
    .map((f) => f.trim())
    .filter((f) => f !== "");

  const newAssignment = {
    id: `asg_${Date.now()}`,
    title,
    description,
    dueDate,
    files
  };

  assignments.push(newAssignment);

  renderTable();
  assignmentForm.reset();
}


// Handle delete button clicks (event delegation)
function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.getAttribute("data-id");

    assignments = assignments.filter((a) => a.id !== id);

    renderTable();
  }
}


// Load initial assignments + set up listeners
async function loadAndInitialize() {
  try {
    const response = await fetch("assignments.json");
    assignments = await response.json();
  } catch (error) {
    console.error("Error loading assignments:", error);
    assignments = [];
  }

  renderTable();

  assignmentForm.addEventListener("submit", handleAddAssignment);
  assignmentsTableBody.addEventListener("click", handleTableClick);
}


// --- Start the App ---
loadAndInitialize();
