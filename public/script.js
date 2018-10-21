document.addEventListener('DOMContentLoaded', ready);

function ready() {
    let formElement = document.getElementsByClassName('js-form')[0];
    let wordElement = formElement.getElementsByClassName('js-form-word')[0];
    let resultElement = document.getElementsByClassName('js-form-result')[0];
    let toTopButtonElement = document.getElementsByClassName('to-top')[0];

    init();

    function init() {
        initEventsListeners();
        initHistoryEvents();
        initForm();
    }

    function initForm() {
        if (window.location.hash) {
            let word = window.location.hash.replace(/^#/, '');
            searchWord(word);
            return;
        }

        wordElement.focus();
    }

    function initHistoryEvents() {
        // noinspection SpellCheckingInspection
        window.onpopstate = function (event) {
            if (event.state && event.state.word) {
                searchWord(event.state.word);
            } else {
                wordElement.value = '';
                resultElement.innerHTML = '';
            }
        };
    }

    function initEventsListeners() {
        formElement.addEventListener('submit', formSubmitHandler);

        toTopButtonElement.addEventListener('click', toTopButtonClickHandler);
    }

    function formSubmitHandler(event) {
        event.preventDefault();
        formSubmit();
    }

    function historyPushState(word) {
        if (!window.history.state || !window.history.state.word || window.history.state.word !== word) {
            window.history.pushState({
                word: word
            }, '', '#' + word);
        }
    }

    function formSubmit() {

        resultElement.innerHTML = 'loading...';

        let data = {word: wordElement.value};
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

            resultElement.innerHTML = '';

            if (event.target.status !== 200) {
                resultElement.innerText = event.target.status + ': ' + event.target.statusText;
                return;
            }

            let response = JSON.parse(event.target.responseText);

            if (response.error) {
                resultElement.innerText = response.error;
                return;
            }

            // noinspection JSUnresolvedVariable
            if (response.results && response.results[0] && response.results[0].lexicalEntries) {
                // noinspection JSUnresolvedVariable
                response.results[0].lexicalEntries.forEach(function (lexicalEntry) {
                    if (lexicalEntry.text) {
                        resultElement.innerHTML += '<h1>' + lexicalEntry.text + '</h1>';
                    }
                    // noinspection JSUnresolvedVariable
                    if (lexicalEntry.lexicalCategory) {
                        resultElement.innerHTML += '<p><b>' + lexicalEntry.lexicalCategory + '</b></p>';
                    }
                    // noinspection JSUnresolvedVariable
                    if (lexicalEntry.pronunciations) {
                        lexicalEntry.pronunciations.forEach(function (pronunciation) {
                            // noinspection JSUnresolvedVariable
                            if (pronunciation.audioFile) {
                                // noinspection JSUnresolvedVariable
                                resultElement.innerHTML += '<audio controls><source src="' + pronunciation.audioFile + '" type="audio/mpeg"></audio>';
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
                                                resultElement.innerHTML += '<p>Definition: ' + chopText(definition) + '</p>';
                                            });
                                        }
                                        // noinspection JSUnresolvedVariable
                                        if (sense.examples) {
                                            sense.examples.forEach(function (example) {
                                                if (example.text) {
                                                    resultElement.innerHTML += '<p>Example: <cite>' + chopText(example.text) + '</cite></p>';
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

            historyPushState(wordElement.value);

            wordElement.value = '';
            wordElement.blur();
            toTopButtonElement.classList.add('_show');
            setTimeout(function () {
                resultElement.scrollIntoView({
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
        searchWord(textWordElement.innerText.replace(/[^A-Za-z]+/, ''));
    }

    window.selectTextWord = selectTextWord;

    function searchWord(word) {
        wordElement.value = word;
        formSubmit();
    }

    function toTopButtonClickHandler() {
        toTopButtonElement.classList.remove('_show');
        wordElement.focus();
    }
}
