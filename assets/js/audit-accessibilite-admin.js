/**
 * Script JavaScript pour l'interface d'administration de l'extension Audit Accessibilité
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/admin
 * @since      1.0.0
 */

(function( $ ) {
	'use strict';

	/**
	 * Toutes les fonctions JavaScript pour l'interface d'administration doivent
	 * être incluses dans cet objet.
	 */
	const AuditAccessibiliteAdmin = {

		/**
		 * Initialisation de l'interface d'administration
		 */
		init: function() {
			this.setupEventListeners();
			this.initializeTooltips();
			this.initializeTabNavigation();
			this.initializeFilterSystem();
		},

		/**
		 * Configuration des écouteurs d'événements
		 */
		setupEventListeners: function() {
			// Gestion du bouton de lancement d'audit
			$('.audit-accessibilite-start-audit').on('click', this.handleStartAudit);
			
			// Gestion des boutons d'exportation
			$('.audit-accessibilite-export-pdf').on('click', this.handleExportPDF);
			$('.audit-accessibilite-export-csv').on('click', this.handleExportCSV);
			
			// Gestion des boutons de filtrage
			$('.audit-accessibilite-filter-button').on('click', this.handleFilterToggle);
			
			// Gestion de la pagination
			$('.audit-accessibilite-pagination a').on('click', this.handlePagination);
			
			// Gestion des accordéons
			$('.audit-accessibilite-accordion-header').on('click', this.toggleAccordion);
		},

		/**
		 * Initialisation des infobulles
		 */
		initializeTooltips: function() {
			$('.audit-accessibilite-tooltip').each(function() {
				const tooltipText = $(this).data('tooltip');
				$(this).append('<span class="audit-accessibilite-tooltip-text">' + tooltipText + '</span>');
			});
		},

		/**
		 * Initialisation de la navigation par onglets
		 */
		initializeTabNavigation: function() {
			$('.audit-accessibilite-tab-button').on('click', function() {
				const tabId = $(this).data('tab');
				
				// Masquer tous les contenus d'onglets
				$('.audit-accessibilite-tab-content').removeClass('active');
				
				// Désactiver tous les boutons d'onglets
				$('.audit-accessibilite-tab-button').removeClass('active');
				
				// Activer l'onglet sélectionné
				$('#' + tabId).addClass('active');
				$(this).addClass('active');
			});
			
			// Activer le premier onglet par défaut
			$('.audit-accessibilite-tab-button:first').trigger('click');
		},

		/**
		 * Initialisation du système de filtrage
		 */
		initializeFilterSystem: function() {
			$('.audit-accessibilite-filter-input').on('input', function() {
				const searchTerm = $(this).val().toLowerCase();
				
				$('.audit-accessibilite-result-item').each(function() {
					const itemText = $(this).text().toLowerCase();
					if (itemText.includes(searchTerm)) {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			});
		},

		/**
		 * Gestion du lancement d'un audit
		 */
		handleStartAudit: function(e) {
			e.preventDefault();
			
			// Récupérer les données du formulaire
			const formData = {
				audit_target: $('#audit_target').val(),
				specific_url: $('#specific_url').val(),
				check_rgaa: $('#check_rgaa').is(':checked') ? 1 : 0,
				check_seo: $('#check_seo').is(':checked') ? 1 : 0,
				check_performance: $('#check_performance').is(':checked') ? 1 : 0,
				check_security: $('#check_security').is(':checked') ? 1 : 0,
				save_report: $('#save_report').is(':checked') ? 1 : 0,
				email_report: $('#email_report').is(':checked') ? 1 : 0,
				email_address: $('#email_address').val(),
				nonce: audit_accessibilite_params.nonce
			};
			
			// Afficher un indicateur de chargement
			$('.audit-accessibilite-loading').show();
			
			// Envoyer la requête AJAX pour lancer l'audit
			$.ajax({
				url: audit_accessibilite_params.ajax_url,
				type: 'POST',
				data: {
					action: 'start_accessibility_audit',
					form_data: formData
				},
				success: function(response) {
					if (response.success) {
						// Mettre à jour l'interface avec les nouveaux résultats
						AuditAccessibiliteAdmin.updateResults(response.data);
					} else {
						// Afficher un message d'erreur
						AuditAccessibiliteAdmin.showNotification(response.data.message, 'error');
					}
				},
				error: function() {
					// Afficher un message d'erreur en cas d'échec
					AuditAccessibiliteAdmin.showNotification('Une erreur est survenue lors du lancement de l\'audit.', 'error');
				},
				complete: function() {
					// Masquer l'indicateur de chargement
					$('.audit-accessibilite-loading').hide();
				}
			});
		},

		/**
		 * Mise à jour des résultats d'audit dans l'interface
		 */
		updateResults: function(data) {
			// Mettre à jour les statistiques
			$('.audit-accessibilite-total-issues').text(data.total_issues);
			$('.audit-accessibilite-open-issues').text(data.open_issues);
			$('.audit-accessibilite-high-priority').text(data.high_priority);
			
			// Mettre à jour le tableau des résultats
			const $resultsTable = $('.audit-accessibilite-results-table tbody');
			$resultsTable.empty();
			
			if (data.issues && data.issues.length > 0) {
				$.each(data.issues, function(index, issue) {
					const priorityClass = 'audit-accessibilite-priority-' + issue.priority.toLowerCase();
					const row = `
						<tr class="audit-accessibilite-result-item ${priorityClass}">
							<td>${issue.id}</td>
							<td>${issue.description}</td>
							<td>${issue.location}</td>
							<td><span class="audit-accessibilite-priority ${priorityClass}">${issue.priority}</span></td>
							<td>${issue.status}</td>
							<td>
								<button class="audit-accessibilite-action-button" data-action="fix" data-id="${issue.id}">Corriger</button>
								<button class="audit-accessibilite-action-button" data-action="ignore" data-id="${issue.id}">Ignorer</button>
							</td>
						</tr>
					`;
					$resultsTable.append(row);
				});
			} else {
				$resultsTable.append('<tr><td colspan="6">Aucun problème d\'accessibilité détecté.</td></tr>');
			}
			
			// Mettre à jour les graphiques
			this.updateCharts(data.chart_data);
			
			// Afficher une notification de succès
			this.showNotification('Audit d\'accessibilité terminé avec succès.', 'success');
		},

		/**
		 * Mise à jour des graphiques
		 */
		updateCharts: function(chartData) {
			// Cette fonction serait implémentée avec une bibliothèque de graphiques comme Chart.js
			// Pour l'instant, c'est juste un espace réservé
			console.log('Mise à jour des graphiques avec les données :', chartData);
		},

		/**
		 * Gestion de l'exportation en PDF
		 */
		handleExportPDF: function(e) {
			e.preventDefault();
			
			// Simuler une requête AJAX pour exporter en PDF
			$.ajax({
				url: audit_accessibilite_params.ajax_url,
				type: 'POST',
				data: {
					action: 'export_accessibility_audit_pdf',
					nonce: audit_accessibilite_params.nonce
				},
				success: function(response) {
					if (response.success) {
						// Télécharger le PDF
						window.location.href = response.data.download_url;
					} else {
						// Afficher un message d'erreur
						AuditAccessibiliteAdmin.showNotification(response.data.message, 'error');
					}
				},
				error: function() {
					// Afficher un message d'erreur en cas d'échec
					AuditAccessibiliteAdmin.showNotification('Une erreur est survenue lors de l\'exportation en PDF.', 'error');
				}
			});
		},

		/**
		 * Gestion de l'exportation en CSV
		 */
		handleExportCSV: function(e) {
			e.preventDefault();
			
			// Simuler une requête AJAX pour exporter en CSV
			$.ajax({
				url: audit_accessibilite_params.ajax_url,
				type: 'POST',
				data: {
					action: 'export_accessibility_audit_csv',
					nonce: audit_accessibilite_params.nonce
				},
				success: function(response) {
					if (response.success) {
						// Télécharger le CSV
						window.location.href = response.data.download_url;
					} else {
						// Afficher un message d'erreur
						AuditAccessibiliteAdmin.showNotification(response.data.message, 'error');
					}
				},
				error: function() {
					// Afficher un message d'erreur en cas d'échec
					AuditAccessibiliteAdmin.showNotification('Une erreur est survenue lors de l\'exportation en CSV.', 'error');
				}
			});
		},

		/**
		 * Gestion du basculement des filtres
		 */
		handleFilterToggle: function() {
			$('.audit-accessibilite-filters-container').toggleClass('active');
		},

		/**
		 * Gestion de la pagination
		 */
		handlePagination: function(e) {
			e.preventDefault();
			
			const page = $(this).data('page');
			
			// Simuler une requête AJAX pour charger la page demandée
			$.ajax({
				url: audit_accessibilite_params.ajax_url,
				type: 'POST',
				data: {
					action: 'get_accessibility_audit_page',
					page: page,
					nonce: audit_accessibilite_params.nonce
				},
				success: function(response) {
					if (response.success) {
						// Mettre à jour le tableau des résultats avec les données de la nouvelle page
						AuditAccessibiliteAdmin.updateResults(response.data);
						
						// Mettre à jour la pagination active
						$('.audit-accessibilite-pagination a').removeClass('active');
						$('.audit-accessibilite-pagination a[data-page="' + page + '"]').addClass('active');
					} else {
						// Afficher un message d'erreur
						AuditAccessibiliteAdmin.showNotification(response.data.message, 'error');
					}
				},
				error: function() {
					// Afficher un message d'erreur en cas d'échec
					AuditAccessibiliteAdmin.showNotification('Une erreur est survenue lors du chargement de la page.', 'error');
				}
			});
		},

		/**
		 * Basculement des accordéons
		 */
		toggleAccordion: function() {
			$(this).toggleClass('active');
			$(this).next('.audit-accessibilite-accordion-content').slideToggle(200);
		},

		/**
		 * Affichage des notifications
		 */
		showNotification: function(message, type) {
			const $notification = $('<div class="audit-accessibilite-notification ' + type + '">' + message + '</div>');
			$('body').append($notification);
			
			// Afficher la notification
			setTimeout(function() {
				$notification.addClass('show');
			}, 10);
			
			// Masquer et supprimer la notification après 3 secondes
			setTimeout(function() {
				$notification.removeClass('show');
				setTimeout(function() {
					$notification.remove();
				}, 300);
			}, 3000);
		}
	};

	// Initialiser l'interface d'administration lorsque le DOM est prêt
	$(document).ready(function() {
		AuditAccessibiliteAdmin.init();
	});

})( jQuery );
