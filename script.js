const API_URL = "api.php";

const eventForm = document.getElementById("eventForm");
const eventId = document.getElementById("eventId");
const titleInput = document.getElementById("title");
const descriptionInput = document.getElementById("description");
const dateInput = document.getElementById("event_date");
const categoryInput = document.getElementById("category");
const statusInput = document.getElementById("status");
const eventTable = document.getElementById("eventTable");
const messageBox = document.getElementById("message");
const submitButton = document.getElementById("submitButton");
const cancelButton = document.getElementById("cancelButton");
const refreshButton = document.getElementById("refreshButton");

function showMessage(message, type = "success") {
    if (!messageBox) {
        alert(message);
        return;
    }

    messageBox.textContent = message;
    messageBox.className = "message " + type;
    messageBox.style.display = "block";

    if (type !== "loading") {
        setTimeout(() => {
            messageBox.style.display = "none";
        }, 4000);
    }
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, options);

    let result;

    try {
        result = await response.json();
    } catch (error) {
        throw new Error("Invalid response from server. Check api.php.");
    }

    if (!response.ok || result.success === false) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

function escapeHTML(value) {
    const element = document.createElement("div");
    element.textContent = value ?? "";
    return element.innerHTML;
}

async function loadEvents() {
    try {
        showMessage("Loading events...", "loading");

        const result = await apiRequest(API_URL);

        const events = Array.isArray(result.data) ? result.data : [];

        eventTable.innerHTML = "";

        if (events.length === 0) {
            eventTable.innerHTML = `
                <tr>
                    <td colspan="7">No events found.</td>
                </tr>
            `;
        } else {
            events.forEach(event => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${escapeHTML(event.event_id)}</td>
                    <td>${escapeHTML(event.title)}</td>
                    <td>${escapeHTML(event.event_date)}</td>
                    <td>${escapeHTML(event.category)}</td>
                    <td>${escapeHTML(event.status)}</td>
                    <td>${escapeHTML(event.description)}</td>
                    <td>
                        <button type="button" class="edit-btn edit"
                            data-id="${escapeHTML(event.event_id)}">
                            Edit
                        </button>
                        <button type="button" class="delete-btn delete"
                            data-id="${escapeHTML(event.event_id)}"
                            data-title="${escapeHTML(event.title)}">
                            Delete
                        </button>
                    </td>
                `;

                eventTable.appendChild(row);
            });
        }

        showMessage(result.message || "Events loaded successfully.");
    } catch (error) {
        showMessage(error.message, "error");
        console.error("Load events error:", error);
    }
}

eventForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const id = eventId.value.trim();

    const eventData = {
        action: id ? "update" : "add",
        event_id: id,
        title: titleInput.value.trim(),
        description: descriptionInput.value.trim(),
        event_date: dateInput.value,
        category: categoryInput.value,
        status: statusInput.value
    };

    if (
        !eventData.title ||
        !eventData.description ||
        !eventData.event_date ||
        !eventData.category ||
        !eventData.status
    ) {
        showMessage("Please fill in all fields.", "error");
        return;
    }

    try {
        submitButton.disabled = true;
        showMessage("Saving event...", "loading");

        const result = await apiRequest(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(eventData)
        });

        showMessage(result.message || "Event saved successfully.");

        resetForm();
        await loadEvents();
    } catch (error) {
        showMessage(error.message, "error");
        console.error("Save event error:", error);
    } finally {
        submitButton.disabled = false;
    }
});

async function editEvent(id) {
    try {
        showMessage("Loading event details...", "loading");

        const result = await apiRequest(
            API_URL + "?id=" + encodeURIComponent(id)
        );

        const event = result.data;

        if (!event) {
            throw new Error("Event not found.");
        }

        eventId.value = event.event_id;
        titleInput.value = event.title;
        descriptionInput.value = event.description;
        dateInput.value = event.event_date;
        categoryInput.value = event.category;
        statusInput.value = event.status;

        submitButton.textContent = "Update Event";

        if (cancelButton) {
            cancelButton.style.display = "inline-block";
        }

        showMessage("Event loaded. Update the details and submit.");
        eventForm.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    } catch (error) {
        showMessage(error.message, "error");
        console.error("Edit event error:", error);
    }
}

async function deleteEvent(id, title) {
    if (!confirm("Are you sure you want to delete '" + title + "'?")) {
        return;
    }

    try {
        showMessage("Deleting event...", "loading");

        const result = await apiRequest(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "delete",
                event_id: id
            })
        });

        showMessage(result.message || "Event deleted successfully.");
        await loadEvents();
    } catch (error) {
        showMessage(error.message, "error");
        console.error("Delete event error:", error);
    }
}

function resetForm() {
    eventForm.reset();
    eventId.value = "";
    submitButton.textContent = "Add Event";

    if (cancelButton) {
        cancelButton.style.display = "none";
    }
}

eventTable.addEventListener("click", function (event) {
    const editButton = event.target.closest(".edit-btn");
    const deleteButton = event.target.closest(".delete-btn");

    if (editButton) {
        editEvent(editButton.dataset.id);
    }

    if (deleteButton) {
        deleteEvent(
            deleteButton.dataset.id,
            deleteButton.dataset.title
        );
    }
});

if (cancelButton) {
    cancelButton.addEventListener("click", resetForm);
}

if (refreshButton) {
    refreshButton.addEventListener("click", loadEvents);
}

loadEvents();