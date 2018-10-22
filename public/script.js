// noinspection JSUnresolvedFunction
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
        // noinspection JSUnresolvedFunction
        formElement.addEventListener('submit', formSubmitHandler);

        // noinspection JSUnresolvedFunction
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

        let word = filterWordString(wordElement.value);
        let data = {word: word};
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
                        // noinspection JSUnresolvedVariable
                        resultElement.innerHTML += '<p><b>' + chopText(lexicalEntry.lexicalCategory) + '</b></p>';
                    }
                    // noinspection JSUnresolvedVariable
                    if (lexicalEntry.pronunciations) {
                        let pronunciations = [];

                        // noinspection JSUnresolvedVariable
                        lexicalEntry.pronunciations.forEach(function (pronunciation) {
                            // noinspection JSUnresolvedVariable
                            if (
                                pronunciation.dialects
                                && pronunciation.phoneticSpelling
                                && pronunciations.indexOf(pronunciation.phoneticSpelling) === -1
                            ) {
                                // noinspection JSUnresolvedVariable
                                pronunciations.push(pronunciation.phoneticSpelling);
                                // noinspection JSUnresolvedVariable
                                resultElement.innerHTML += '<p>' + chopText(pronunciation.dialects.join(', ')) + ': <code>' + pronunciation.phoneticSpelling + '</code></p>';
                            }

                            // noinspection JSUnresolvedVariable
                            if (pronunciation.audioFile) {
                                // noinspection JSUnresolvedVariable
                                resultElement.innerHTML += '<audio controls><source src="' + pronunciation.audioFile + '" type="audio/mpeg"></audio>';
                            }
                        });
                    }

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
                                            resultElement.innerHTML += '<p>' + chopText('Definition') + ': ' + chopText(definition) + '</p>';
                                        });
                                    }
                                    // noinspection JSUnresolvedVariable
                                    if (sense.examples) {
                                        // noinspection JSUnresolvedVariable
                                        sense.examples.forEach(function (example) {
                                            if (example.text) {
                                                resultElement.innerHTML += '<p>' + chopText('Example') + ': <cite>' + chopText(example.text) + '</cite></p>';
                                            }
                                        });
                                    }
                                    // noinspection JSUnresolvedVariable
                                    if (sense.short_definitions) {
                                        // noinspection JSUnresolvedVariable
                                        sense.short_definitions.forEach(function (shortDefinition) {
                                            if (shortDefinition) {
                                                resultElement.innerHTML += '<p>' + chopText('Short definition') + ': <cite>' + chopText(shortDefinition) + '</cite></p>';
                                            }
                                        });
                                    }
                                });
                            }
                        });
                    }

                    if (lexicalEntry.text) {
                        resultElement.innerHTML += '<p>';
                        resultElement.innerHTML += '<a target="_blank" href="https://translate.google.com/#en/ru/' + lexicalEntry.text + '">Translate</a>, ';
                        resultElement.innerHTML += '<a target="_blank" href="https://www.google.ru/search?q=' + lexicalEntry.text + '&tbm=isch">images</a>';
                        resultElement.innerHTML += '</p>';
                    }
                });
            }

            historyPushState(word);

            wordElement.value = '';
            wordElement.blur();
            toTopButtonElement.classList.add('_show');
            setTimeout(function () {
                resultElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        }
    }

    function filterWordString(word) {
        return word.trim().toLowerCase().replace(/[^a-z]+/, '');
    }

    function chopText(text) {
        let result = '';

        let textWords = text.split(' ');
        textWords.forEach(function (textWord) {
            result += ' ' + '<span class="text-word js-text-word" onclick="selectTextWord(this)">' + textWord + '</span>';
        });

        return result;
    }

    function selectTextWord(textWordElement) {
        searchWord(textWordElement.innerText.replace(/[^A-Za-z]+/, ''));
    }

    window.selectTextWord = selectTextWord;

    function searchWord(word) {
        word = filterWordString(word);
        wordElement.value = word;
        formSubmit();
    }

    function toTopButtonClickHandler() {
        toTopButtonElement.classList.remove('_show');
        wordElement.focus();
    }
}
