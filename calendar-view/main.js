const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { RichText } = wp.blockEditor;

registerBlockType('vz-appointment-manager/calendar-view', {
  title: __('Viroz Appointment Calendar', 'vz-appointment-manager'),
  icon: 'calendar-alt',
  category: 'widgets',
  attributes: {
    content: {
      type: 'string',
      source: 'html',
      selector: 'p',
    },
  },
  edit: (props) => {
    const { attributes: { content }, setAttributes } = props;

    const onChangeContent = (newContent) => {
      setAttributes({ content: newContent });
    };

    return (
      <RichText
        tagName="p"
        className={props.className}
        onChange={onChangeContent}
        value={content}
      />
    );
  },
  save: (props) => {
    return (
      <RichText.Content tagName="p" value={props.attributes.content} />
    );
  },
});