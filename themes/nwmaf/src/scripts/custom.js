$(document).ready(function () {


    // ===== Footnotes =======//
    // add this to main.js inside doc ready //

    const footnotes = document.querySelectorAll('[data-footnote]');
    const textRefs = document.querySelectorAll('[data-reference]');
    const backLinks = document.querySelectorAll('[data-back-link]');

    for (let i = 0; i < textRefs.length; i++) {
        const reference = textRefs[i];

        // focus events
        reference.addEventListener('focusin', (e) => {
            e.target.classList.add('has-focus');
        });

        reference.addEventListener('focusout', (e) => {
            e.target.classList.remove('has-focus');
        });

        // arrow keys
        textRefs[i].addEventListener('keydown', function (e) {

            if (e.keyCode == 37) {
                if (this.previousElementSibling) {
                    this.previousElementSibling.focus();
                }
            } else if (e.keyCode == 39) {
                if (this.nextElementSibling) {
                    this.nextElementSibling.focus();
                }
            }

            // tab
            if (e.keyCode == 9) {
                if (textRefs[i].classList.contains('has-focus')) {
                    textRefs[i].classList.remove('has-focus');
                }
                textRefs[i].classList.add('has-focus');
            }
        });
    }

    for (let i = 0; i < backLinks.length; i++) {
        const backLink = backLinks[i];

        // find link in text on click and add focus indicator class
        backLink.addEventListener('click', (e) => {
            let textRef = e.target.getAttribute('href').replace(/#/gi, '');
            let linkToFocus = document.getElementById(`${textRef}`);

            linkToFocus.classList.add('has-focus');

            for (let i = 0; i < textRefs.length; i++) {
                if ((textRefs[i].classList.contains('has-focus')) && (textRefs[i] !== linkToFocus)) {
                    textRefs[i].classList.remove('has-focus');
                }
            }
        });

        // find link in text on Enter and add focus indicator class
        backLink.addEventListener('keydown', (e) => {

            if (e.keyCode === 13) {
                let textRef = e.target.getAttribute('href').replace(/#/gi, '');
                let linkToFocus = document.getElementById(`${textRef}`);

                linkToFocus.classList.add('has-focus');

                for (let i = 0; i < textRefs.length; i++) {
                    if ((textRefs[i].classList.contains('has-focus')) && (textRefs[i] !== linkToFocus)) {
                        textRefs[i].classList.remove('has-focus');
                    }
                }
            }
        });
    }
    // End Footnotes //

    console.log('helloo!');

});


