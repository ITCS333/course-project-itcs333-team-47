// src/assignments/admin.js


const API_BASE = "api/index.php?resource=assignments";

let assignments = [];
let editingId = null; 


const assignmentForm   = document.getElementById("assignment-form");
const titleInput       = document.getElementById("assignment-title");
const descInput        = document.getElementById("assignment-description");
const dueInput         = document.getElementById("assignment-due-date");
const filesInput       = document.getElementById("assignment-files");
const submitButton     = document.getElementById("add-assignment");


const assignmentsTableBody = document.getElementById("assignments-tbody");


function parseFiles(text) {
  return text
    .split("\n")
    .map(f => f.trim())
    .filter(f => f !== "");
}


function filesToTextarea(filesArray) {
  if (!Array.isArray(filesArray)) return "";
  return filesArray.join("\n");
}

function createAssignmentRow(assignment) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.due_date}</td>
    <td>
      <button class="edit-btn" data-id="${assignment.id}">Edit</button>
      <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
  `;

  return tr;
}


function renderTable() {
  assignmentsTableBody.innerHTML = "";

  assignments.forEach(a => {
    const row = createAssignmentRow(a);
    assignmentsTableBody.appendChild(row);
  });
}

async function loadAssignments() {
  try {
    const res = await fetch(API_BASE);
    const json = await res.json();

    if (!json.success) {
      console.error(json);
      alert("Failed to load assignments.");
      return;
    }

    assignments = json.data || [];
    renderTable();
  } catch (err) {
    console.error(err);
    alert("Error fetching assignments.");
  }
}

async function handleFormSubmit(event) {
  event.preventDefault();

  const title = titleInput.value.trim();
  const description = descInput.value.trim();
  const due_date = dueInput.value;
  const files = parseFiles(filesInput.value);

  if (!title || !description || !due_date) {
    alert("Please fill in title, description, and due date.");
    return;
  }

  const payload = { title, description, due_date, files };

  try {
    if (editingId === null) {
      const res = await fetch(API_BASE, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const json = await res.json();

      if (!json.success) {
        console.error(json);
        alert("Failed to create assignment.");
        return;
      }

      assignments.push(json.data);
      renderTable();
      assignmentForm.reset();

    } else {
      payload.id = editingId;

      const res = await fetch(API_BASE, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const json = await res.json();

      if (!json.success) {
        console.error(json);
        alert("Failed to update assignment.");
        return;
      }

      const idx = assignments.findIndex(a => a.id === editingId);
      if (idx !== -1) {
        assignments[idx] = {
          ...assignments[idx],
          title,
          description,
          due_date,
          files
        };
      }

      renderTable();
      assignmentForm.reset();
      editingId = null;
      submitButton.textContent = "Add Assignment";
    }
  } catch (err) {
    console.error(err);
    alert("Error while saving assignment.");
  }
}

async function handleTableClick(event) {
  const btn = event.target;
  if (!(btn instanceof HTMLElement)) return;

  const id = btn.getAttribute("data-id");
  if (!id) return;

  if (btn.classList.contains("delete-btn")) {
    const confirmDelete = confirm("Are you sure you want to delete this assignment?");
    if (!confirmDelete) return;

    try {
      const res = await fetch(`${API_BASE}&id=${id}`, {
        method: "DELETE"
      });
      const json = await res.json();

      if (!json.success) {
        console.error(json);
        alert("Failed to delete assignment.");
        return;
      }

      assignments = assignments.filter(a => String(a.id) !== String(id));
      renderTable();
    } catch (err) {
      console.error(err);
      alert("Error deleting assignment.");
    }
  }

  if (btn.classList.contains("edit-btn")) {
    const assignment = assignments.find(a => String(a.id) === String(id));
    if (!assignment) {
      alert("Assignment not found in current list.");
      return;
    }

    titleInput.value = assignment.title;
    descInput.value = assignment.description;
    dueInput.value = assignment.due_date;
    filesInput.value = filesToTextarea(assignment.files);

    editingId = assignment.id;
    submitButton.textContent = "Save Changes";

    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

assignmentForm.addEventListener("submit", handleFormSubmit);
assignmentsTableBody.addEventListener("click", handleTableClick);

loadAssignments();
