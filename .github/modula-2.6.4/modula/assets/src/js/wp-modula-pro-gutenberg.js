import ModulaProGalleryImageInner from './components/ModulaProGalleryImageInner';

const { __ } = wp.i18n;
const { Fragment, useEffect, useState } = wp.element;
const { useSelect, withSelect } = wp.data;
const { getBlobByURL, isBlobURL, revokeBlobURL } = wp.blob;
const { SelectControl, Button, Spinner, Toolbar, IconButton } = wp.components;
const { createHigherOrderComponent } = wp.compose;
const { BlockControls, MediaUpload } = wp.blockEditor;

const withProFilters = createHigherOrderComponent((ModulaItemsExtraComponent) => {
	return (props) => {

		useEffect(() => {

			if (undefined != props.attributes.settings.filters && props.attributes.settings.filters.length > 1) {
				props.setAttributes({ modulaDivClassName: galleryContainerExtraClassName});
			} else {
				props.setAttributes({ modulaDivClassName: ''});
			}
		}, []);

		if ( undefined == props.attributes.settings.filters || 1 == props.attributes.settings.filters.length ) {
			return <ModulaItemsExtraComponent />;
		}

		if( 'slider' == props.attributes.settings.type ) {
			return <ModulaItemsExtraComponent />;
		}
		const { position, attributes } = props;
		const {
			settings: {
				filters,
				dropdownFilters,
				filterClick,
				hideAllFilter,
				allFilterLabel,
				filterStyle,
				filterLinkColor,
				filterLinkHoverColor,
				defaultActiveFilter,
				filterPositioning,
				filterTextAlignment,
				enableCollapsibleFilters,
				collapsibleActionText
			}
		} = attributes;
		let filtersAlignmnent = '';
		let galleryContainerExtraClassName = '';
		if ('left' == filterPositioning || 'right' == filterPositioning || 'left_right' == filterPositioning) {
			galleryContainerExtraClassName = 'vertical-filters';
			filtersAlignmnent = 'vertical-filters ';
			if ('left' == filterPositioning) {
				filtersAlignmnent += 'left-vertical';
			} else if ('right' == filterPositioning) {
				filtersAlignmnent += 'right-vertical';
			} else {
				filtersAlignmnent += 'both-vertical';
			}
		} else {
			filtersAlignmnent =
				'top_bottom' == filterPositioning ? 'horizontal-filters both-horizontal' : 'horizontal-filters';
		}



		const filterRender = (
			<div className={`filters styled-menu menu--${filterStyle} ${filtersAlignmnent}`}>
				<ul className="modula_menu__list">
					<li className="modula_menu__item modula_menu__item--current">
						<a
							data-filter="all"
							href="#"
							class={`${'All' == defaultActiveFilter ? 'selected' : ''} modula_menu__link`}
						>
							{' '}
							{allFilterLabel}
						</a>
					</li>
					{filters.length > 1 &&
						filters.map((filter) => {
							if (filter != '') {
								return [
									<Fragment>
										<li className="modula_menu__item">
											<a
												data-filter={filter}
												href={`#jtg-filter-${filter}`}
												class={`${filter == defaultActiveFilter
													? 'selected'
													: ''} modula_menu__link `}
											>
												{' '}
												{filter}{' '}
											</a>
										</li>
									</Fragment>
								];
							}
						})}
				</ul>
			</div>
		);

		if (position == 'top') {
			if (
				'top' == filterPositioning ||
				'top_bottom' == filterPositioning ||
				'left' == filterPositioning ||
				'left_right' == filterPositioning
			) {
				return filterRender;
			}
		} else {
			if (
				'bottom' == filterPositioning ||
				'top_bottom' == filterPositioning ||
				'right' == filterPositioning ||
				'left_right' == filterPositioning
			) {
				return filterRender;
			}
		}
		return null;
	};
}, 'withProFilters');

wp.hooks.addFilter('modula.ModulaItemsExtraComponent', 'modula/modulaProFilters', withProFilters, 1);

const withProFiltersImage = createHigherOrderComponent((ModulaGalleryImage) => {
	return (props) => {
		const { images, settings, id, effectCheck } = props.attributes;

		const { img, index, setAttributes, checkHoverEffect } = props;

		if (undefined == settings.filters || 1 == settings.filters.length) {
			return <ModulaGalleryImage {...props} />;
		}

		if( 'slider' == settings.type ) {
			return <ModulaGalleryImage {...props} />
		}
		let filterClassName = '';

		let filters = img.filters.split(',');

		filters = filters.forEach((filter) => {
			filterClassName += `jtg-filter-${filter} `;
			return filterClassName;
		});

		return (
			<div
				className={`modula-item effect-${settings.effect} jtg-filter-all ${filterClassName}`}
				data-width={img['data-width'] ? img['data-width'] : '2'}
				data-height={img['data-height'] ? img['data-height'] : '2'}
			>
				<div className="modula-item-overlay" />

				<div className="modula-item-content">
					<img
						className={`modula-image pic`}
						data-id={img.id}
						data-full={img.src}
						data-src={img.src}
						data-valign="middle"
						data-halign="center"
						src={img.src}
					/>
					{'slider' !== settings.type && (
						<ModulaProGalleryImageInner
							settings={settings}
							img={img}
							index={index}
							hideTitle={undefined != effectCheck && effectCheck.title == true ? false : true}
							hideDescription={undefined != effectCheck && effectCheck.description == true ? false : true}
							hideSocial={undefined != effectCheck && effectCheck.social == true ? false : true}
							effectCheck={effectCheck}
						/>
					)}
				</div>
			</div>
		);
	};
}, 'withProFiltersImage');

wp.hooks.addFilter('modula.ModulaGalleryImage', 'modula/modulaProGalleryImageFilters', withProFiltersImage, 99);


// Add proInstalled atrribute so we can overide the upsell button
const withProCheck = createHigherOrderComponent(( ModulaEdit) => {

	return (props) => {
		useEffect(() => {
			props.setAttributes({proInstalled: true});
		}, [])
		return <ModulaEdit {...props} />
	}
}, 'withProCheck')
wp.hooks.addFilter('modula.ModulaEdit', 'modula/modulaProCheck', withProCheck);