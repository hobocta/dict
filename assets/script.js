import { default as axios } from './vendor/axios/axios.index.js';

// noinspection JSUnresolvedFunction
document.addEventListener('DOMContentLoaded', ready);

function ready() {
    let formElement = document.getElementsByClassName('js-form')[0];
    let languageElement = formElement.getElementsByClassName('js-form-language')[0];
    let wordElement = formElement.getElementsByClassName('js-form-word')[0];
    let resultElement = document.getElementsByClassName('js-form-result')[0];
    let wordIdsElement = document.getElementsByClassName('js-word-ids')[0];
    let toTopButtonElement = document.getElementsByClassName('js-to-top')[0];

    init();

    function init() {
        initEventsListeners();
        initHistoryEvents();
        initForm();
    }

    function initEventsListeners() {
        // noinspection JSUnresolvedFunction
        languageElement.addEventListener('change', languageElementHandler);

        // noinspection JSUnresolvedFunction
        formElement.addEventListener('submit', formSubmitHandler);

        // noinspection JSUnresolvedFunction
        toTopButtonElement.addEventListener('click', toTopButtonClickHandler);
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

    function initForm() {
        initLanguageValue();
        initWordIds(languageElement.value);

        if (window.location.hash) {
            let word = window.location.hash.replace(/^#/, '');
            searchWord(word);
            return;
        }

        wordElement.focus();
    }

    function initLanguageValue() {
        const languageValue = localStorage.getItem(getLanguageValueKey());

        if (languageValue) {
            languageElement.value = languageValue;
        }
    }

    function getLanguageValueKey() {
        return 'language-value';
    }

    function initWordIds(languageValue) {
        if (!languageValue) {
            return;
        }

        let wordIds = getWordIds(languageValue);

        wordIdsElement.innerHTML = '';

        if (wordIds.length) {
            wordIdsElement.innerHTML += 'History: ';

            wordIds.forEach(function (wordId) {
                wordIdsElement.innerHTML += chopText(wordId);
            });
        }
    }

    function getWordIds(languageValue) {
        let wordIds = [];

        let wordIdsString = localStorage.getItem(getWordIdsValueKey(languageValue));

        if (wordIdsString) {
            wordIds = JSON.parse(wordIdsString);
        }

        return wordIds;
    }

    function saveLanguageValue(languageValue) {
        localStorage.setItem(getLanguageValueKey(), languageValue);
    }

    function saveWord(languageValue, wordId) {
        let wordIds = getWordIds(languageValue);

        if (wordIds.indexOf(wordId) !== -1) {
            return;
        }

        wordIds.push(wordId);

        localStorage.setItem(getWordIdsValueKey(languageValue), JSON.stringify(wordIds));

        initWordIds(languageValue);
    }

    function getWordIdsValueKey(languageValue) {
        return 'word-ids-' + languageValue;
    }

    function formSubmitHandler(event) {
        event.preventDefault();
        formSubmit();
    }

    function languageElementHandler() {
        saveLanguageValue(languageElement.value);
        initWordIds(languageElement.value);
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

        const wordId = filterWordString(wordElement.value);

        const languageValue = languageElement.value;
        const language = filterLanguageString(languageValue);
        const [sourceLanguageId, targetLanguageId] = language.split('-');

        const data = {
            wordId: wordId,
            sourceLanguageId: sourceLanguageId,
            targetLanguageId: targetLanguageId
        };

        // noinspection JSUnresolvedReference
        axios.post('api/translation', data)
            .then(function (response) {
                if (response.data.error) {
                    showError(response.data.error);

                    return;
                }

                saveWord(languageValue, wordId);
                historyPushState(wordId);
                showResults(response.data);

                wordElement.blur();
                toTopButtonElement.classList.add('_show');
            })
            .catch(function (error) {
                showError(error);
            });
    }

    function filterWordString(string) {
        return string.trim().toLowerCase();
    }

    function filterLanguageString(string) {
        return string.trim().toLowerCase().replace(/[^a-z-]+/, '');
    }

    function chopText(text) {
        let result = '';

        if (typeof text !== 'string') {
            return result;
        }

        let textWords = text.split(' ');

        textWords.forEach(function (textWord) {
            result += ' ' + '' +
                '<span class="text-word js-text-word" onclick="selectTextWord(this)">' +
                textWord +
                '</span>';
        });

        return result;
    }

    function selectTextWord(textWordElement) {
        searchWord(textWordElement.innerText.replace(/[^A-Za-z]+/, ''));
    }

    window.selectTextWord = selectTextWord;

    function searchWord(wordId) {
        wordId = filterWordString(wordId);
        wordElement.value = wordId;
        formSubmit();
    }

    function toTopButtonClickHandler() {
        toTopButtonElement.classList.remove('_show');
        wordElement.focus();
    }

    function showError(error) {
        resultElement.innerText = error;
    }

    function showResults(response) {
        resultElement.innerHTML = '';

        // noinspection JSUnresolvedVariable
        if (!response.results || !response.results[0] || !response.results[0].lexicalEntries) {
            return;
        }

        // noinspection JSUnresolvedVariable
        response.results[0].lexicalEntries.forEach(function (lexicalEntry) {
            showResultsLexicalEntry(lexicalEntry);
        });
    }

    function showResultsLexicalEntry(lexicalEntry) {
        showResultsLexicalEntryLexicalHeader(lexicalEntry);
        showResultsLexicalEntryLexicalCategory(lexicalEntry);
        showResultsLexicalEntryPronunciations(lexicalEntry);
        showResultsLexicalEntryEntries(lexicalEntry);
        showResultsLexicalEntryFooter(lexicalEntry);
    }

    function showResultsLexicalEntryLexicalHeader(lexicalEntry) {
        if (lexicalEntry.text) {
            resultElement.innerHTML += '<h1>' + lexicalEntry.text + '</h1>';
        }
    }

    function showResultsLexicalEntryLexicalCategory(lexicalEntry) {
        // noinspection JSUnresolvedVariable
        if (lexicalEntry.lexicalCategory) {
            // noinspection JSUnresolvedVariable
            resultElement.innerHTML += '<p><b>' + chopText(lexicalEntry.lexicalCategory) + '</b></p>';
        }
    }

    function showResultsLexicalEntryPronunciations(lexicalEntry) {
        // noinspection JSUnresolvedVariable
        if (lexicalEntry.pronunciations) {
            let pronunciations = [];

            // noinspection JSUnresolvedVariable
            lexicalEntry.pronunciations.forEach(function (pronunciation) {
                // noinspection JSUnresolvedVariable
                if (
                    pronunciation.dialects &&
                    pronunciation.phoneticSpelling &&
                    pronunciations.indexOf(pronunciation.phoneticSpelling) === -1
                ) {
                    // noinspection JSUnresolvedVariable
                    pronunciations.push(pronunciation.phoneticSpelling);
                    // noinspection JSUnresolvedVariable
                    resultElement.innerHTML += '<p>' +
                        chopText(pronunciation.dialects.join(', ')) +
                        ': <code>' + pronunciation.phoneticSpelling + '</code></p>';
                }

                // noinspection JSUnresolvedVariable
                if (pronunciation.audioFile) {
                    // noinspection JSUnresolvedVariable
                    resultElement.innerHTML += '<audio controls><source src="' +
                        pronunciation.audioFile + '" type="audio/mpeg"></audio>';
                }
            });
        }
    }

    function showResultsLexicalEntryEntries(lexicalEntry) {
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
                                resultElement.innerHTML += '<p>' +
                                    chopText('Definition') +
                                    ': ' + chopText(definition) +
                                    '</p>';
                            });
                        }
                        // noinspection JSUnresolvedVariable
                        if (sense.short_definitions) {
                            // noinspection JSUnresolvedVariable
                            sense.short_definitions.forEach(function (shortDefinition) {
                                if (shortDefinition) {
                                    resultElement.innerHTML += '<p>' +
                                        chopText('Short definition') +
                                        ': ' + chopText(shortDefinition) +
                                        '</p>';
                                }
                            });
                        }
                        // noinspection JSUnresolvedVariable
                        if (sense.examples) {
                            // noinspection JSUnresolvedVariable
                            sense.examples.forEach(function (example) {
                                if (example.text) {
                                    resultElement.innerHTML += '<p>' +
                                        chopText('Example') + ': <cite>' +
                                        chopText(example.text) +
                                        '</cite></p>';
                                }
                            });
                        }
                        // noinspection JSUnresolvedVariable
                        if (sense.translations) {
                            // noinspection JSUnresolvedVariable
                            sense.translations.forEach(function (translation) {
                                if (translation.text) {
                                    resultElement.innerHTML += '<p>' +
                                        chopText('Translation') +
                                        ': <cite>' + chopText(translation.text) +
                                        '</cite></p>';
                                }
                            });
                        }
                    });
                }
            });
        }
    }

    function showResultsLexicalEntryFooter(lexicalEntry) {
        if (lexicalEntry.text) {
            let translateUrl = 'https://translate.google.com/?sl=en&op=translate&text=' + lexicalEntry.text;
            let imagesUrl = 'https://www.google.ru/search?q=' + lexicalEntry.text + '&tbm=isch';

            resultElement.innerHTML += '<p>';
            resultElement.innerHTML += '<a target="_blank" href="' + translateUrl + '">Translate</a>, ';
            resultElement.innerHTML += '<a target="_blank" href="' + imagesUrl + '">images</a>';
            resultElement.innerHTML += '</p>';
        }
    }
}
