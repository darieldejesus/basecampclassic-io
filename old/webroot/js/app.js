/*! Basecamp IO Project Manager app.js
 * ===================================
 * 
 * @Author  Dariel de Jesus
 * @Email   <darieldejesus@gmail.com>
 * @version 1.0
 */

// Verify jQuery exists before app.js
if (typeof jQuery === "undefined") {
    throw new Error("jQuery is required.");
}

(function ($) {

    "use strict";

    var updateStatusProject = function(button, id, status) {
        $.ajax({
            method: "POST",
            url: "/update",
            data: { id: id, status: status },
            beforeSend: function( ) {
                button.button("loading");
            }
        }).done(function(httpCode) {
            button.button("reset");
            if (httpCode == 200) {
                button.parents("tr").remove();
            }
        });
    }

    var updateCheckedFiles = function(checkbox, id, status) {
        $.ajax({
            method: "POST",
            url: "/update-checked-files",
            data: { id: id, status: status },
            beforeSend: function() {
                checkbox.siblings('span').show();
            }
        }).done(function(updated) {
            if (!updated) {
                checkbox.prop('checked', !status);
            }
            checkbox.siblings('span').hide();
        });
    }

    var verifyFilesChecked = function(projectId) {
        var filesCheched = $("#files-archived-" + projectId);
        if (filesCheched.length) {
            if (!filesCheched[0].checked) {
                alert("Files should be archived before proceed.");
                return false;
            }
        }
        return true;
    }

    var getProjectId = function(button) {
        var parent = button.parents("tr");
        if (!parent) { return false; }
        var hiddenInput = parent.find(".project-id");
        if (!hiddenInput) { return false; }
        return hiddenInput.val();
    }

    var activateProject = function() {
        var projectId = getProjectId($(this));
        updateStatusProject($(this), projectId, "active");
    };

    var holdProject = function() {
        var projectId = getProjectId($(this));
        updateStatusProject($(this), projectId, "on_hold");
    };

    var archiveProject = function() {
        var projectId = getProjectId($(this));
        if (verifyFilesChecked(projectId)) {
            updateStatusProject($(this), projectId, "archived");
        }
    };

    var deleteProject = function() {
        var projectId = getProjectId($(this));
        var homeId = $("#home-id").val();
        var domain = $("#domain").val();
        var editURL = "https://" + homeId + "." + domain + "/projects/" + projectId + "/edit";
        var bcWindow = window.open(editURL, "_blank");
        console.log(bcWindow.document);
        $(bcWindow.document).load(function(){
            alert("Loaded");
        });
        console.log(bcWindow);
    };

    var addOptionListener = function(index, button) {
        var action = $(button).attr("id");
        if (action == "btn-activate") {
            $(button).on("click", activateProject);
        } else if (action == "btn-hold") {
            $(button).on("click", holdProject);
        } else if (action == "btn-archive") {
            $(button).on("click", archiveProject);
        } else if (action == "btn-delete") {
            $(button).on("click", deleteProject);
        }
    };

    var addCheckboxListener = function(index, checkbox) {
        checkbox = $(checkbox);
        checkbox.change(function(event) {
            var projectId = getProjectId( checkbox );
            updateCheckedFiles( checkbox, projectId, this.checked );
        });
    };

    var init = function() {
        $(".btn-project-option").each(addOptionListener);
        $(".files-archived-checkbox").each(addCheckboxListener);
    }

    init();
}(jQuery));
