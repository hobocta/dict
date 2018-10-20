document.addEventListener('DOMContentLoaded', ready);

function ready() {
    let form = document.getElementsByClassName('js-form')[0];
    let word = form.getElementsByClassName('js-form-word')[0];
    let resultContainer = document.getElementsByClassName('js-form-result')[0];

    form.addEventListener('submit', formSubmitHandler);
    function formSubmitHandler(event) {
        event.preventDefault();

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

            if (event.target.status !== 200) {
                throw event.target.status + ': ' + event.target.statusText;
            }

            let response = JSON.parse(event.target.responseText);

            if (response.error) {
                resultContainer.innerText = response.error;
                return;
            }

            // noinspection JSUnresolvedVariable
            response.results[0].lexicalEntries.forEach(function (lexicalEntry) {
                console.log('lexicalEntry', lexicalEntry);
                // noinspection JSUnresolvedVariable
                resultContainer.innerHTML += '<p><b>' + lexicalEntry.lexicalCategory + '</b></p>';
                // noinspection JSUnresolvedVariable
                lexicalEntry.pronunciations.forEach(function (pronunciation) {
                    // noinspection JSUnresolvedVariable
                    if (pronunciation.audioFile) {
                        // noinspection JSUnresolvedVariable
                        resultContainer.innerHTML += '<audio controls><source src="' + pronunciation.audioFile + '" type="audio/mpeg"></audio>';
                    }
                });
                lexicalEntry.entries.forEach(function (entry) {
                    // noinspection JSUnresolvedVariable
                    entry.senses.forEach(function (sense) {
                        // noinspection JSUnresolvedVariable
                        sense.definitions.forEach(function (definition) {
                            resultContainer.innerHTML += '<p>' + definition + '</p>';
                        });
                    });
                });
            });
        }
    }
}
