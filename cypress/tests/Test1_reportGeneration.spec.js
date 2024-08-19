function getNowDateAndHour() {
    let now = new Date().toISOString();
    const charactersToRemove = ['-', ':', 'T'];
    let nowFormatted = '';

    for (let i = 0; i < now.length; i++) {
        let shouldRemove = false;
        
        for (let j = 0; j < charactersToRemove.length; j++) {
            if (now[i] === charactersToRemove[j]) {
                shouldRemove = true;
                break;
            }
        }

        if (!shouldRemove) {
            nowFormatted += now[i];
        }
    }

    return (nowFormatted.split('.')[0]);
}

describe("SciELO Submissions Report - Report generation", function() {
    it("Asserts presence of report setting fields", function() {
        cy.login('dbarnes', null, 'publicknowledge');

        cy.contains('a.app__navItem', 'Reports').click();
        cy.contains('a', 'SciELO Submissions Report').click();

        cy.contains('h2', 'Period');
        cy.contains('Select the desired filtering type:');
        cy.get('#selectFilterTypeDate').within(() => {
            cy.contains('option', 'Filter by submitted date');
            cy.contains('option', 'Filter by final decision date');
            cy.contains('option', 'Filter by submitted date and final decision');
        });
        cy.get('#selectFilterTypeDate').select('Filter by submitted date');
        cy.contains('Submitted date range');
        cy.get('#selectFilterTypeDate').select('Filter by final decision date');
        cy.contains('Final decision date range');
        cy.get('#selectFilterTypeDate').select('Filter by submitted date and final decision');
        cy.contains('Submitted date range');
        cy.contains('Final decision date range');
        cy.get('#selectFilterTypeDate').select('Filter by submitted date');
        
        cy.contains('h2', 'Sections');
        cy.contains('label', 'Articles');
        cy.contains('label', 'Reviews');

        cy.contains('The report generation proccesss can take a few minutes, depending on the parameters selected and the number of submissions present in the system.');
        cy.contains('input', 'Generate Report');
    });
    it('Generates CSV report', function () {
        cy.login('dbarnes', null, 'publicknowledge');

        cy.contains('a.app__navItem', 'Reports').click();
        cy.contains('a', 'SciELO Submissions Report').click();

        cy.get('#selectFilterTypeDate').select('Filter by submitted date');
        cy.contains('label', 'Articles').parent().within(() => {
            cy.get('input').check();
        });
        cy.contains('label', 'Reviews').parent().within(() => {
            cy.get('input').check();
        });

        cy.contains('Generate Report').click();
        cy.wait(2000);

        let now = getNowDateAndHour();
        const downloadsFolder = Cypress.config('downloadsFolder');
        const reportFileName = 'submissionsJPKJPK-' + now + '.csv';

        cy.readFile(downloadsFolder + reportFileName, 'utf-8').then((text) => {
            expect(text).to.contain('Articles,Reviews');
        });
    });
});