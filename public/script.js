document.addEventListener('DOMContentLoaded', ready);

function ready() {
    let form = document.getElementsByClassName('js-form')[0];
    let word = form.getElementsByClassName('js-form-word')[0];
    let resultContainer = document.getElementsByClassName('js-form-result')[0];
    let toTopButton = document.getElementsByClassName('to-top')[0];

    word.focus();

    form.addEventListener('submit', formSubmitHandler);

    function formSubmitHandler(event) {
        event.preventDefault();
        formSubmit();
    }

    function formSubmit() {

        resultContainer.innerHTML = 'loading...';

        let data = {word: word.value};
        let json = JSON.stringify(data);

        let xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/word/', true);
        xhr.setRequestHeader('Content-type', 'application/json; charset=utf-8');
        xhr.send(json);
        /**
         * @param event.target
         */
        xhr.onreadystatechange = function (event) {
            if (event.target.readyState !== 4) return;

            resultContainer.innerHTML = '';

            if (event.target.status !== 200) {
                resultContainer.innerText = event.target.status + ': ' + event.target.statusText;
                return;
            }

            let response = JSON.parse(event.target.responseText);

            if (response.error) {
                resultContainer.innerText = response.error;
                return;
            }

            // noinspection JSUnresolvedVariable
            if (response.results && response.results[0] && response.results[0].lexicalEntries) {
                // noinspection JSUnresolvedVariable
                response.results[0].lexicalEntries.forEach(function (lexicalEntry) {
                    if (lexicalEntry.text) {
                        resultContainer.innerHTML += '<h1>' + lexicalEntry.text + '</h1>';
                    }
                    // noinspection JSUnresolvedVariable
                    if (lexicalEntry.lexicalCategory) {
                        resultContainer.innerHTML += '<p><b>' + lexicalEntry.lexicalCategory + '</b></p>';
                    }
                    // noinspection JSUnresolvedVariable
                    if (lexicalEntry.pronunciations) {
                        lexicalEntry.pronunciations.forEach(function (pronunciation) {
                            // noinspection JSUnresolvedVariable
                            if (pronunciation.audioFile) {
                                // noinspection JSUnresolvedVariable
                                resultContainer.innerHTML += '<audio controls><source src="' + pronunciation.audioFile + '" type="audio/mpeg"></audio>';
                            }
                        });
                        if (lexicalEntry.entries) {
                            lexicalEntry.entries.forEach(function (entry) {
                                // noinspection JSUnresolvedVariable
                                if (entry.senses) {
                                    // noinspection JSUnresolvedVariable
                                    entry.senses.forEach(function (sense) {
                                        // noinspection JSUnresolvedVariable
                                        if (sense.definitions) {
                                            // noinspection JSUnresolvedVariable
                                            sense.definitions.forEach(function (definition) {
                                                resultContainer.innerHTML += '<p>Definition: ' + chopText(definition) + '</p>';
                                            });
                                        }
                                        // noinspection JSUnresolvedVariable
                                        if (sense.examples) {
                                            sense.examples.forEach(function (example) {
                                                if (example.text) {
                                                    resultContainer.innerHTML += '<p>Example: <cite>' + chopText(example.text) + '</cite></p>';
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    }
                });
            }

            word.value = '';
            word.blur();
            toTopButton.classList.add('_show');
            setTimeout(function () {
                resultContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 250);
        }
    }

    function chopText(text) {
        let result = '';

        let textWords = text.split(' ');
        textWords.forEach(function (textWord) {
            result += ' ' + '<span class="js-text-word" onclick="selectTextWord(this)">' + textWord + '</span>';
        });

        return result;
    }

    function selectTextWord(textWordElement) {
        word.value = textWordElement.innerText.replace(/[^A-Za-z]+/, '');
        formSubmit();
    }

    window.selectTextWord = selectTextWord;

    toTopButton.addEventListener('click', toTopButtonClickHandler);

    function toTopButtonClickHandler() {
        toTopButton.classList.remove('_show');
        word.focus();
    }
}
