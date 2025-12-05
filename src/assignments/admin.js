/*
  Requirement: Make the "Manage Assignments" page interactive.
*/

// --- Global Data Store ---
let assignments = [];

// --- Element Selections ---
const assignmentForm = document.getElementById("assignment-form");
const assignmentsTableBody = document.getElementById("assignments-tbody");

// Create table row
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

function renderTable() {
  assignmentsTableBody.innerHTML = "";
  assignments.forEach(a => assignmentsTableBody.appendChild(createAssignmentRow(a)));
}

function handleAddAssignment(event) {
  event.preventDefault();

  const title = document.getElementById("assignment-title").value;
  const description = document.getElementById("assignment-description").value;
  const dueDate = document.getElementById("assignment-due-date").value;
  const filesText = document.getElementById("assignment-files").value;

  const files = filesText.split("\n").map(f => f.trim()).filter(f => f !== "");

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

function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;
    assignments = assignments.filter(a => a.id !== id);
    renderTable();
  }
}

async function loadAndInitialize() {
  try {
    const res = await fetch("api/assignments.json"); // FIXED
    assignments = await res.json();
  } catch (err) {
    console.error("Error loading assignments:", err);
    assignments = [];
  }

  renderTable();
  assignmentForm.addEventListener("submit", handleAddAssignment);
  assignmentsTableBody.addEventListener("click", handleTableClick);
}

loadAndInitialize();
