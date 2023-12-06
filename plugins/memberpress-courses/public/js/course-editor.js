(function ($) {
  window.meprCourseEditor = class CourseEditor {
    constructor() {
      this.initialize();
    }

    initialize() {
    }

    showCurriculumView() {
      this.toggleVisualEditor();
      this.toggleMetaBoxes("show", ["mpcs-course-builder"]); // Show selected
      this.togglePanels("show", "mepr-curriculum-panel"); //Hides all except "mepr-curriculum-panel" (the Lessons and Quizzes panesl)
    };

    showSettingsView() {
      this.toggleVisualEditor();
      this.toggleMetaBoxes("show", ["mpcs-course-settings"]); // Show selected
      this.togglePanels("hide", "");
    };

    showCertificatesView() {
      this.toggleVisualEditor();
      this.toggleMetaBoxes("show", ["mpcs-course-certificates"]); // Show selected
      this.togglePanels("hide", "");
    };

    showDefaultView() {
      this.toggleVisualEditor("show");

      this.toggleMetaBoxes("hide", [
        "mpcs-course-builder",
        "mpcs-course-settings",
        "mpcs-course-certificates",
      ]);

      this.togglePanels("hide", "mepr-curriculum-panel");
    }

    // Hide Meta Boxes
    toggleMetaBoxes(action, elements = []) {
      const metaboxes = wp.data.select("core/edit-post").getAllMetaBoxes();
      Object.entries(metaboxes).map(([key, metabox]) => {
        const metaboxEl = $("#" + metabox.id);

        if (action == "hide") {
          if (elements == "" || elements.includes(metabox.id)) {
            metaboxEl.hide();
            return false;
          }
          if (!metaboxEl.hasClass("is-hidden")) metaboxEl.show();
        } else {
          if (elements == "" || elements.includes(metabox.id)) {
            if(metaboxEl.hasClass('closed')){
              metaboxEl.removeClass('closed')
            }
            metaboxEl.show();
            return false;
          }
          metaboxEl.hide();
        }
      });
    }

    // Hide Panels
    togglePanels(action, element = "") {
      $(".components-panel .components-panel__body").each(function () {
        if (action == "hide") {
          if (element == "" || this.className.includes(element)) {
            $(this).hide();
          } else {
            $(this).show();
          }
        } else {
          if (element == "" || this.className.includes(element)) {
            $(this).show();
            $(this).addClass("is-opened");

            // Open Lesson Panel
            if( false == wp.data.select("core/edit-post").isEditorPanelOpened('mpcs-lesson-panel/mpcs-lessons') ){
              wp.data.dispatch("core/edit-post").toggleEditorPanelOpened('mpcs-lesson-panel/mpcs-lessons')
            }

            //Open Quizzes Panel
            if( false == wp.data.select("core/edit-post").isEditorPanelOpened('mpcs-quiz-panel/mpcs-quizzes') ){
              wp.data.dispatch("core/edit-post").toggleEditorPanelOpened('mpcs-quiz-panel/mpcs-quizzes')
            }
          } else {
            $(this).hide();
          }
        }
      });
    }

    // Hide Visual Editor
    toggleVisualEditor(visibility = "hide") {
      if (visibility == "show") {
        $(".edit-post-visual-editor, .edit-post-text-editor").show();
      } else {
        $(".edit-post-visual-editor, .edit-post-text-editor").hide();
      }
    }

    addCSS(selector, rule) {
      $(selector).css(rule);
    }

    showPrompt(url) {
      const { __ } = wp.i18n;
      const html = `<div class="mpcs-vex-dialog">
        <h2>` + __("Leave Course Editor", "memberpress-courses") + `?</h2>
        <p>` + __("Changes you made may not be saved", "memberpress-courses") + `</br></p>
      </div>`;

      vex.dialog.confirm({
        unsafeMessage: html,
        className: "vex-theme-plain mpcs-vex",
        buttons: [
          $.extend({}, vex.dialog.buttons.NO, {
            text: __("Cancel", "memberpress-courses"),
            className: "button button-tertiary"
          }),
          $.extend({}, vex.dialog.buttons.YES, {
            text: __("Discard & Exit", "memberpress-courses"),
            className: 'button button-secondary',
            click: function (event) {
              vex.close(this);
              window.location.href = url;
            },
          }),
          $.extend({}, vex.dialog.buttons.YES, {
            text: __("Save & Exit", "memberpress-courses"),
            className: "button button-primary",
            click: function () {
              const isSavingMetaboxes = wp.data.select("core/edit-post").isSavingMetaBoxes;

              let wasSaving = false;

              wp.data.subscribe(() => {
                let isSaving = isSavingMetaboxes();

                if (wasSaving && ! isSaving) {
                  window.location.assign(url);
                }

                wasSaving = isSaving;
              });

              wp.data.dispatch("core/editor").savePost();
              vex.close(this);
            }
          })
        ],
        callback: function (value) {},
      });
    }

    replaceFullscreenLogo() {
      $(".edit-post-fullscreen-mode-close")
        .css("background-color", "#184499")
        .html(
          '<img src="' +
            MPCS_Course_Data.imagesUrl +
            '/memberpress-logo-white.png">'
        )
        .attr("href", MPCS_Course_Data.coursesUrl);
    }

    updateFullscreenLogoLink() {
      $(".edit-post-fullscreen-mode-close").attr("href", MPCS_Course_Data.coursesUrl);
    }

    isGutenbergEditor() {
      // return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined'
      return document.body.classList.contains("block-editor-page");
    }

    maybeHideHeader(sidebarIsOpen, isTabletOrSmaller) {
      if (sidebarIsOpen && isTabletOrSmaller) {
        $("#mpcs-admin-header-wrapper").hide();
      } else {
        $("#mpcs-admin-header-wrapper").show();
      }
    }

    setHeaderHeight() {
      let h = $("#mpcs-admin-header").outerHeight(true) || 0;
      $(".edit-post-layout").css("padding-top", h + "px");
    }

    maybeHideBlockInserter(tabIndex) {
      let blockInserterBtn = $('.edit-post-header-toolbar__inserter-toggle');

      if(tabIndex == 'course') {
        blockInserterBtn.show();
      } else {
        blockInserterBtn.hide();
      }
    }
  };
})(jQuery);
