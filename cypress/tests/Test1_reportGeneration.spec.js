describe("SciELO Submissions Report - Report generation", function() {
    it("Generates report", function() {
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
});